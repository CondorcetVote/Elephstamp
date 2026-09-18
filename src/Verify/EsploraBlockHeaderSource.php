<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use CondorcetVote\ElephStamp\Exception\{BlockSourceException, InvalidInputException};

/**
 * Block headers from any server speaking the Esplora HTTP API: mempool.space,
 * blockstream.info, or a self-hosted instance.
 *
 * The source is asked for the block hash at a height, then for the raw
 * header of that hash. The header is parsed and its proof of work checked
 * locally, and the hash it produces must be the one the source announced:
 * an explorer cannot make a bogus merkle root pass without forging a valid
 * header for it.
 *
 * The explorer is untrusted input, so the transport is hardened like the
 * calendar client: https only, no redirects, small response cap, timeouts.
 */
final class EsploraBlockHeaderSource implements BlockHeaderSource
{
    /**
     * Every answer we read is a short hex string or a number.
     */
    private const int MAX_RESPONSE_BYTES = 1_000;

    private readonly HttpClientInterface $httpClient;

    private readonly string $baseUrl;

    /**
     * @param string      $baseUrl     e.g. `https://mempool.space/api`
     * @param float       $timeout     idle timeout in seconds
     * @param float       $maxDuration hard cap in seconds on a whole request
     * @param string|null $label       name shown to users; defaults to the host
     *
     * @throws InvalidInputException on a non-https base URL
     */
    public function __construct(
        string $baseUrl,
        ?HttpClientInterface $httpClient = null,
        private readonly string $userAgent = 'ElephStamp',
        private readonly float $timeout = 10.0,
        private readonly float $maxDuration = 30.0,
        private readonly ?string $label = null,
    ) {
        if (!str_starts_with($baseUrl, 'https://') || parse_url($baseUrl, \PHP_URL_HOST) === null) {
            throw new InvalidInputException(\sprintf('Explorer URL must be an https URL, got: %s', $baseUrl));
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function blockHeader(int $height): BlockHeader
    {
        $hashHex = strtolower($this->get('block-height/' . $height, \sprintf('block %d', $height)));

        if (!ctype_xdigit($hashHex) || \strlen($hashHex) !== 64) {
            throw new BlockSourceException(\sprintf('%s returned an invalid hash for block %d', $this->describe(), $height));
        }

        $headerHex = strtolower($this->get('block/' . $hashHex . '/header', \sprintf('header of block %d', $height)));
        $rawHeader = ctype_xdigit($headerHex) && \strlen($headerHex) === 2 * BlockHeader::RAW_LENGTH ? hex2bin($headerHex) : false;

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
        $body = $this->get('blocks/tip/height', 'chain tip');

        if (!ctype_digit($body)) {
            throw new BlockSourceException(\sprintf('%s returned an invalid chain tip height', $this->describe()));
        }

        return (int) $body;
    }

    public function describe(): string
    {
        return $this->label ?? (string) parse_url($this->baseUrl, \PHP_URL_HOST);
    }

    /**
     * Fetch a small text answer.
     *
     * @throws BlockSourceException on any transport, status or size problem
     */
    private function get(string $path, string $what): string
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/' . $path, [
                'headers' => ['User-Agent' => $this->userAgent, 'Accept' => 'text/plain'],
                // A redirect could send us to a host the user never chose.
                'max_redirects' => 0,
                'timeout' => $this->timeout,
                'max_duration' => $this->maxDuration,
            ]);

            $status = $response->getStatusCode();

            if ($status === 404) {
                $response->cancel();

                throw new BlockSourceException(\sprintf('%s does not know the %s', $this->describe(), $what));
            }

            if ($status !== 200) {
                $response->cancel();

                throw new BlockSourceException(\sprintf('%s returned status %d for the %s', $this->describe(), $status, $what));
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

        return trim($body);
    }
}
