<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use CondorcetVote\ElephStamp\Exception\{BlockSourceException, InvalidInputException};
use JsonException;
use SensitiveParameter;

/**
 * Block headers from a Bitcoin node through its JSON-RPC interface: Bitcoin
 * Core (or anything speaking the same protocol, such as a hosted RPC
 * provider) answering `getblockhash`, `getblockheader` and `getblockcount`.
 *
 * Only headers are asked for, so a pruned node is enough. As with the
 * explorer driver, the node is asked for the block hash at a height, then
 * for the raw 80-byte header of that hash; the header is parsed and its
 * proof of work checked locally, and its hash must be the one the node
 * announced.
 *
 * Credentials are given one of three ways: `user:password@` in the URL,
 * the `$user`/`$password` arguments, or the path to the `.cookie` file
 * Bitcoin Core writes in its data directory. They never appear in error
 * messages or in {@see describe()}.
 *
 * Plain `http` is accepted only for local and private hosts (`localhost`,
 * a single-label hostname such as a Docker service name, loopback, RFC 1918
 * and link-local addresses), where a node usually lives; any other host must
 * be reached over `https`. Redirects are never followed and answers are
 * size-capped.
 */
final class BitcoinRpcBlockHeaderSource implements BlockHeaderSource
{
    /**
     * A raw header is 160 hex characters; the envelope and an error message
     * fit comfortably in the rest.
     */
    private const int MAX_RESPONSE_BYTES = 8_000;

    private const string REQUEST_ID = 'elephstamp';

    /**
     * Bitcoin Core's `RPC_INVALID_PARAMETER`: `getblockhash` past the tip.
     */
    private const int RPC_INVALID_PARAMETER = -8;

    /**
     * Bitcoin Core's `RPC_INVALID_ADDRESS_OR_KEY`: `getblockheader` of an unknown hash.
     */
    private const int RPC_INVALID_ADDRESS_OR_KEY = -5;

    private readonly HttpClientInterface $httpClient;

    /**
     * The endpoint, stripped of any credentials.
     */
    private readonly string $url;

    private readonly string $host;

    /**
     * `user:password`, or null when the node needs none.
     */
    private readonly ?string $credentials;

    /**
     * @param string      $url         e.g. `http://127.0.0.1:8332`, `http://user:password@127.0.0.1:8332`, or a provider's https endpoint
     * @param string|null $user        RPC user (`rpcuser`), with $password
     * @param string|null $password    RPC password (`rpcpassword`), with $user
     * @param string|null $cookieFile  path to Bitcoin Core's `.cookie` file (e.g. `~/.bitcoin/.cookie`), instead of a user and password
     * @param float       $timeout     idle timeout in seconds
     * @param float       $maxDuration hard cap in seconds on a whole request
     * @param string|null $label       name shown to users; defaults to the host
     *
     * @throws InvalidInputException on an unusable URL, plain http to a public host, or inconsistent credentials
     */
    public function __construct(
        #[SensitiveParameter]
        string $url,
        ?HttpClientInterface $httpClient = null,
        ?string $user = null,
        #[SensitiveParameter]
        ?string $password = null,
        ?string $cookieFile = null,
        private readonly string $userAgent = 'ElephStamp',
        private readonly float $timeout = 10.0,
        private readonly float $maxDuration = 30.0,
        private readonly ?string $label = null,
    ) {
        $parts = parse_url($url);
        $scheme = \is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';
        $host = \is_array($parts) ? ($parts['host'] ?? null) : null;

        if (!\is_array($parts) || $host === null || $host === '' || !\in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidInputException(\sprintf('Node URL must be an http or https URL, got: %s', self::redact($url)));
        }

        if ($scheme === 'http' && !self::isLocalHost($host)) {
            throw new InvalidInputException(\sprintf('Plain http is only accepted for local and private hosts; use https to reach %s', $host));
        }

        $this->host = $host . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $this->url = $scheme . '://' . $this->host . ($parts['path'] ?? '') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        $this->credentials = self::resolveCredentials($parts, $user, $password, $cookieFile);
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function blockHeader(int $height): BlockHeader
    {
        $what = \sprintf('block %d', $height);
        $hashHex = $this->call('getblockhash', [$height], $what);

        if (!\is_string($hashHex) || !ctype_xdigit($hashHex) || \strlen($hashHex) !== 64) {
            throw new BlockSourceException(\sprintf('%s returned an invalid hash for block %d', $this->describe(), $height));
        }

        $hashHex = strtolower($hashHex);
        $headerHex = $this->call('getblockheader', [$hashHex, false], \sprintf('header of block %d', $height));
        $rawHeader = \is_string($headerHex) && ctype_xdigit($headerHex) && \strlen($headerHex) === 2 * BlockHeader::RAW_LENGTH ? hex2bin($headerHex) : false;

        if ($rawHeader === false) {
            throw new BlockSourceException(\sprintf('%s returned an invalid header for block %d', $this->describe(), $height));
        }

        $header = BlockHeader::fromRawHeader($height, $rawHeader);

        if (!hash_equals($hashHex, $header->hashHex())) {
            throw new BlockSourceException(\sprintf('%s returned a header for block %d whose hash does not match the announced block hash', $this->describe(), $height));
        }

        return $header;
    }

    public function tipHeight(): int
    {
        $count = $this->call('getblockcount', [], 'chain tip');

        if (!\is_int($count) || $count < 0) {
            throw new BlockSourceException(\sprintf('%s returned an invalid chain tip height', $this->describe()));
        }

        return $count;
    }

    public function describe(): string
    {
        return $this->label ?? $this->host;
    }

    /**
     * One JSON-RPC call, returning its decoded `result`.
     *
     * Bitcoin Core answers RPC errors with an HTTP 500 (or 404 for an unknown
     * method) and the JSON error in the body, so the body is read whatever
     * the status; only an outright authentication or access refusal is
     * reported from the status alone.
     *
     * @param list<mixed> $params
     *
     * @throws BlockSourceException on any transport, status, size or protocol problem, and on an RPC error
     */
    private function call(string $method, array $params, string $what): mixed
    {
        $options = [
            'headers' => ['User-Agent' => $this->userAgent, 'Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'body' => json_encode(['jsonrpc' => '1.0', 'id' => self::REQUEST_ID, 'method' => $method, 'params' => $params], \JSON_THROW_ON_ERROR),
            // A redirect could send the credentials to a host the user never chose.
            'max_redirects' => 0,
            'timeout' => $this->timeout,
            'max_duration' => $this->maxDuration,
        ];

        if ($this->credentials !== null) {
            $options['auth_basic'] = $this->credentials;
        }

        try {
            $response = $this->httpClient->request('POST', $this->url, $options);
            $status = $response->getStatusCode();

            if ($status === 401 || $status === 403) {
                $response->cancel();

                throw new BlockSourceException(\sprintf('%s refused the request with HTTP %d: check the RPC credentials and the node\'s rpcallowip', $this->describe(), $status));
            }

            $body = '';

            foreach ($this->httpClient->stream($response) as $chunk) {
                $body .= $chunk->getContent();

                if (\strlen($body) > self::MAX_RESPONSE_BYTES) {
                    $response->cancel();

                    throw new BlockSourceException(\sprintf('%s answer for the %s exceeded the size limit', $this->describe(), $what));
                }
            }
        } catch (HttpClientException $exception) {
            throw new BlockSourceException(\sprintf('%s: %s', $this->describe(), $exception->getMessage()), previous: $exception);
        }

        try {
            $document = json_decode($body, associative: true, depth: 8, flags: \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $document = null;
        }

        if (!\is_array($document)) {
            throw $status === 200
                ? new BlockSourceException(\sprintf('%s returned an invalid answer for the %s', $this->describe(), $what))
                : new BlockSourceException(\sprintf('%s returned status %d for the %s', $this->describe(), $status, $what));
        }

        if (isset($document['error'])) {
            throw $this->rpcError($document['error'], $what);
        }

        if (!\array_key_exists('result', $document)) {
            throw new BlockSourceException(\sprintf('%s returned an answer without result for the %s', $this->describe(), $what));
        }

        return $document['result'];
    }

    private function rpcError(mixed $error, string $what): BlockSourceException
    {
        $code = \is_array($error) && \is_int($error['code'] ?? null) ? $error['code'] : null;
        $message = \is_array($error) && \is_string($error['message'] ?? null) ? $error['message'] : 'unknown error';

        if ($code === self::RPC_INVALID_PARAMETER || $code === self::RPC_INVALID_ADDRESS_OR_KEY) {
            return new BlockSourceException(\sprintf('%s does not know the %s', $this->describe(), $what));
        }

        if (\is_array($error) && \is_string($error['data'] ?? null) && $error['data'] !== '') {
            $message .= ' (' . $error['data'] . ')';
        }

        return new BlockSourceException(\sprintf('%s answered the %s with RPC error %s: %s', $this->describe(), $what, $code ?? '?', $message));
    }

    /**
     * @param array<string, int|string> $urlParts
     *
     * @throws InvalidInputException when credentials come from several places, or half of a pair is missing
     */
    private static function resolveCredentials(array $urlParts, ?string $user, #[SensitiveParameter] ?string $password, ?string $cookieFile): ?string
    {
        $fromUrl = isset($urlParts['user']) ? rawurldecode((string) $urlParts['user']) . ':' . rawurldecode((string) ($urlParts['pass'] ?? '')) : null;
        $fromArguments = $user !== null || $password !== null;
        $ways = ($fromUrl !== null ? 1 : 0) + ($fromArguments ? 1 : 0) + ($cookieFile !== null ? 1 : 0);

        if ($ways > 1) {
            throw new InvalidInputException('Give the node credentials one way only: in the URL, as user and password, or as a cookie file');
        }

        if ($fromArguments) {
            if ($user === null || $password === null) {
                throw new InvalidInputException('Node user and password go together');
            }

            return $user . ':' . $password;
        }

        if ($cookieFile !== null) {
            return self::readCookieFile($cookieFile);
        }

        return $fromUrl;
    }

    /**
     * Bitcoin Core writes `__cookie__:<random>` in `.cookie` at startup.
     *
     * @throws InvalidInputException when the file cannot be read or holds no credentials
     */
    private static function readCookieFile(string $path): string
    {
        $content = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        if ($content === false) {
            throw new InvalidInputException(\sprintf('Cannot read the node cookie file %s', $path));
        }

        $content = trim($content);

        if (!str_contains($content, ':')) {
            throw new InvalidInputException(\sprintf('The node cookie file %s does not hold "user:password" credentials', $path));
        }

        return $content;
    }

    /**
     * Whether a host is one plain http is acceptable for: a name without a
     * domain (localhost, a Docker service), or a loopback, private or
     * link-local address.
     */
    private static function isLocalHost(string $host): bool
    {
        $ip = trim($host, '[]');

        if (filter_var($ip, \FILTER_VALIDATE_IP) !== false) {
            return filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE) === false;
        }

        return !str_contains($host, '.');
    }

    /**
     * A URL for an error message, with any credentials blanked out.
     */
    private static function redact(#[SensitiveParameter] string $url): string
    {
        return preg_replace('~//[^/@]*@~', '//***@', $url) ?? $url;
    }
}
