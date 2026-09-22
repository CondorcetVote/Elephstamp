<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\{BlockSourceException, InvalidInputException};
use CondorcetVote\ElephStamp\Verify\{BitcoinRpcBlockHeaderSource, CrossCheckingBlockHeaderSource};
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * A JSON-RPC answer shaped like Bitcoin Core's: `jsonrpc` echoed as sent,
 * an RPC error carried in the body with an HTTP 500.
 */
function rpcResult(mixed $result): MockResponse
{
    return new MockResponse(json_encode(['result' => $result, 'error' => null, 'id' => 'elephstamp'], \JSON_THROW_ON_ERROR), ['response_headers' => ['content-type' => 'application/json']]);
}

function rpcError(int $code, string $message, int $httpStatus = 500, ?string $data = null): MockResponse
{
    $error = ['code' => $code, 'message' => $message];

    if ($data !== null) {
        $error['data'] = $data;
    }

    return new MockResponse(json_encode(['result' => null, 'error' => $error, 'id' => 'elephstamp'], \JSON_THROW_ON_ERROR), ['http_code' => $httpStatus]);
}

/**
 * A mock Bitcoin Core knowing block 967571 and a tip at 967620.
 *
 * @param array<string, MockResponse|string> $overrides         responses keyed by RPC method, to break one answer
 * @param list<array<string, mixed>>         $requests          every request seen, with the decoded JSON-RPC body
 * @param array<string, mixed>               $constructorArguments
 */
function rpcNode(array $overrides = [], array &$requests = [], string $url = 'http://rpc:secret@127.0.0.1:8332', array $constructorArguments = []): BitcoinRpcBlockHeaderSource
{
    $client = new MockHttpClient(function (string $httpMethod, string $url, array $options) use ($overrides, &$requests): MockResponse {
        $body = json_decode((string) $options['body'], true, flags: \JSON_THROW_ON_ERROR);
        $requests[] = [
            'http_method' => $httpMethod,
            'url' => $url,
            'body' => $body,
            'authorization' => $options['normalized_headers']['authorization'][0] ?? null,
            'content_type' => $options['normalized_headers']['content-type'][0] ?? null,
        ];

        $method = $body['method'];

        if (isset($overrides[$method])) {
            return $overrides[$method] instanceof MockResponse ? $overrides[$method] : new MockResponse($overrides[$method]);
        }

        return match ($method) {
            'getblockhash' => $body['params'] === [967_571] ? rpcResult(BLOCK_HASH_967571) : rpcError(-8, 'Block height out of range'),
            'getblockheader' => $body['params'] === [BLOCK_HASH_967571, false] ? rpcResult(HEADER_967571) : rpcError(-5, 'Block not found'),
            'getblockcount' => rpcResult(967_620),
            default => rpcError(-32601, 'Method not found', 404),
        };
    });

    return new BitcoinRpcBlockHeaderSource($url, $client, ...$constructorArguments);
}

it('asks for the hash then the raw header over JSON-RPC, and checks they agree', function (): void {
    $requests = [];
    $source = rpcNode(requests: $requests);

    $header = $source->blockHeader(967_571);

    expect($header->hashHex())->toBe(BLOCK_HASH_967571)
        ->and($header->merkleRootHex())->toBe('098f1d278e9fbe9493f1b4fc0ddb3af3d9bb203cf254be2a4f48a3037ad0bfab')
        ->and($header->rawHeader)->toBe(hex2bin(HEADER_967571))
        ->and($source->tipHeight())->toBe(967_620)
        ->and($source->describe())->toBe('127.0.0.1:8332')
        ->and(array_column($requests, 'body'))->toBe([
            ['jsonrpc' => '1.0', 'id' => 'elephstamp', 'method' => 'getblockhash', 'params' => [967_571]],
            ['jsonrpc' => '1.0', 'id' => 'elephstamp', 'method' => 'getblockheader', 'params' => [BLOCK_HASH_967571, false]],
            ['jsonrpc' => '1.0', 'id' => 'elephstamp', 'method' => 'getblockcount', 'params' => []],
        ])
        ->and(array_unique(array_column($requests, 'http_method')))->toBe(['POST'])
        ->and(array_unique(array_column($requests, 'url')))->toBe(['http://127.0.0.1:8332/'])
        ->and(array_unique(array_column($requests, 'authorization')))->toBe(['Authorization: Basic ' . base64_encode('rpc:secret')])
        ->and(array_unique(array_column($requests, 'content_type')))->toBe(['Content-Type: application/json']);
});

it('accepts a JSON-RPC 2.0 envelope and an error carried with HTTP 200, as hosted providers send', function (): void {
    $requests = [];
    $source = rpcNode([
        'getblockcount' => new MockResponse('{"jsonrpc":"2.0","result":968142,"id":"elephstamp"}'),
        'getblockhash' => new MockResponse('{"jsonrpc":"2.0","error":{"code":-32603,"message":"Internal error","data":"upstream request failed"},"id":"elephstamp"}'),
    ], $requests, 'https://shared.eu-central-1.getblock.io/4c5ed6efcdfa4557b13b441d8c701634');

    expect($source->tipHeight())->toBe(968_142)
        ->and($source->describe())->toBe('shared.eu-central-1.getblock.io')
        ->and($requests[0]['url'])->toBe('https://shared.eu-central-1.getblock.io/4c5ed6efcdfa4557b13b441d8c701634')
        ->and($requests[0]['authorization'])->toBeNull();

    try {
        $source->blockHeader(967_571);
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toBe('shared.eu-central-1.getblock.io answered the block 967571 with RPC error -32603: Internal error (upstream request failed)');
    }
});

it('takes credentials from arguments or from a cookie file, and sends none when there are none', function (): void {
    $dir = makeTempDir();
    file_put_contents($dir . '/.cookie', "__cookie__:6f1c2b3a4d5e\n");

    $requests = [];
    rpcNode(requests: $requests, url: 'http://localhost:18443', constructorArguments: ['user' => 'alice', 'password' => 'p@ss:word'])->tipHeight();
    rpcNode(requests: $requests, url: 'http://localhost:18443', constructorArguments: ['cookieFile' => $dir . '/.cookie'])->tipHeight();
    rpcNode(requests: $requests, url: 'http://localhost:18443')->tipHeight();
    rpcNode(requests: $requests, url: 'http://al%40ice:p%3Ass@localhost:18443')->tipHeight();

    expect(array_column($requests, 'authorization'))->toBe([
        'Authorization: Basic ' . base64_encode('alice:p@ss:word'),
        'Authorization: Basic ' . base64_encode('__cookie__:6f1c2b3a4d5e'),
        null,
        'Authorization: Basic ' . base64_encode('al@ice:p:ss'),
    ]);
});

it('refuses ambiguous or incomplete credentials', function (array $arguments, string $url, string $message): void {
    $dir = makeTempDir();
    file_put_contents($dir . '/.cookie', '__cookie__:x');
    file_put_contents($dir . '/no-colon', 'nothing');

    $arguments = array_map(static fn(mixed $value): mixed => \is_string($value) ? str_replace('{dir}', $dir, $value) : $value, $arguments);

    try {
        new BitcoinRpcBlockHeaderSource($url, new MockHttpClient, ...$arguments);
        $this->fail('No exception thrown');
    } catch (InvalidInputException $exception) {
        expect($exception->getMessage())->toContain($message);
    }
})->with([
    'url and arguments' => [['user' => 'a', 'password' => 'b'], 'http://a:b@localhost', 'one way only'],
    'arguments and cookie' => [['user' => 'a', 'password' => 'b', 'cookieFile' => '{dir}/.cookie'], 'http://localhost', 'one way only'],
    'url and cookie' => [['cookieFile' => '{dir}/.cookie'], 'http://a:b@localhost', 'one way only'],
    'user alone' => [['user' => 'a'], 'http://localhost', 'go together'],
    'password alone' => [['password' => 'b'], 'http://localhost', 'go together'],
    'missing cookie file' => [['cookieFile' => '{dir}/missing'], 'http://localhost', 'Cannot read the node cookie file'],
    'cookie without credentials' => [['cookieFile' => '{dir}/no-colon'], 'http://localhost', 'does not hold "user:password"'],
]);

it('reports an unknown block from the RPC error codes Bitcoin Core uses', function (): void {
    try {
        rpcNode()->blockHeader(99_999_999);
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toBe('127.0.0.1:8332 does not know the block 99999999');
    }

    try {
        rpcNode(['getblockhash' => rpcResult(str_repeat('ab', 32))])->blockHeader(967_571);
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toBe('127.0.0.1:8332 does not know the header of block 967571');
    }
});

it('reports other RPC errors with their code and message', function (): void {
    try {
        rpcNode(['getblockcount' => rpcError(-28, 'Loading block index…')])->tipHeight();
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toBe('127.0.0.1:8332 answered the chain tip with RPC error -28: Loading block index…');
    }

    try {
        rpcNode(['getblockcount' => new MockResponse('{"result":null,"error":"boom","id":"elephstamp"}')])->tipHeight();
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toBe('127.0.0.1:8332 answered the chain tip with RPC error ?: unknown error');
    }
});

it('reports refused credentials and access', function (int $status): void {
    try {
        rpcNode(['getblockcount' => new MockResponse('', ['http_code' => $status])])->tipHeight();
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toContain('refused the request with HTTP ' . $status)
            ->toContain('credentials')
            ->not->toContain('secret');
    }
})->with([401, 403]);

it('rejects a header that does not hash to the announced block hash', function (): void {
    $genesis = '0100000000000000000000000000000000000000000000000000000000000000000000003ba3edfd7a7b12b27ac72c3e67768f617fc81bc3888a51323a9fb8aa4b1e5e4a29ab5f49ffff001d1dac2b7c';

    rpcNode(['getblockheader' => rpcResult($genesis)])->blockHeader(967_571);
})->throws(BlockSourceException::class, 'does not match the announced block hash');

it('rejects malformed answers', function (string $method, MockResponse $response, string $message): void {
    $source = rpcNode([$method => $response]);

    try {
        $method === 'getblockcount' ? $source->tipHeight() : $source->blockHeader(967_571);
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toContain($message);
    }
})->with([
    'hash of the wrong type' => ['getblockhash', rpcResult(967_571), 'invalid hash'],
    'hash of the wrong length' => ['getblockhash', rpcResult('abcd'), 'invalid hash'],
    'header of the wrong type' => ['getblockheader', rpcResult(['merkleroot' => 'x']), 'invalid header'],
    'header of the wrong length' => ['getblockheader', rpcResult('abcd'), 'invalid header'],
    'tip as a string' => ['getblockcount', rpcResult('967620'), 'invalid chain tip'],
    'negative tip' => ['getblockcount', rpcResult(-1), 'invalid chain tip'],
    'not JSON' => ['getblockcount', new MockResponse('<html>'), 'invalid answer'],
    'a JSON scalar' => ['getblockcount', new MockResponse('42'), 'invalid answer'],
    'no result' => ['getblockcount', new MockResponse('{"id":"elephstamp"}'), 'without result'],
    'gateway error without JSON' => ['getblockcount', new MockResponse('Bad Gateway', ['http_code' => 502]), 'status 502'],
    'oversized' => ['getblockcount', new MockResponse('{"result":"' . str_repeat('9', 10_000) . '"}'), 'size limit'],
]);

it('reports transport errors', function (): void {
    $down = new BitcoinRpcBlockHeaderSource('http://localhost:8332', new MockHttpClient(static fn() => throw new TransportException('connection refused')));

    try {
        $down->tipHeight();
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toBe('localhost:8332: connection refused');
    }
});

it('accepts plain http for local and private hosts only', function (string $url, ?string $describe): void {
    $source = new BitcoinRpcBlockHeaderSource($url, new MockHttpClient);

    expect($source->describe())->toBe($describe);
})->with([
    ['http://localhost:8332', 'localhost:8332'],
    ['http://bitcoind:8332', 'bitcoind:8332'],
    ['http://127.0.0.1:8332', '127.0.0.1:8332'],
    ['http://[::1]:8332', '[::1]:8332'],
    ['http://10.0.0.5:8332', '10.0.0.5:8332'],
    ['http://172.16.4.2', '172.16.4.2'],
    ['http://192.168.1.10:8332/', '192.168.1.10:8332'],
    ['https://rpc.example.com/v1/token', 'rpc.example.com'],
    ['https://1.2.3.4:8332', '1.2.3.4:8332'],
]);

it('refuses plain http to a public host and unusable URLs, without leaking credentials', function (string $url, string $message): void {
    try {
        new BitcoinRpcBlockHeaderSource($url, new MockHttpClient);
        $this->fail('No exception thrown');
    } catch (InvalidInputException $exception) {
        expect($exception->getMessage())->toContain($message)
            ->not->toContain('secret');
    }
})->with([
    'public name' => ['http://rpc.example.com:8332', 'use https to reach rpc.example.com'],
    'public IPv4' => ['http://1.2.3.4:8332', 'use https to reach 1.2.3.4'],
    'public IPv6' => ['http://[2001:4860::8888]:8332', 'use https'],
    'ftp' => ['ftp://localhost', 'http or https URL'],
    'no host' => ['http://', 'http or https URL'],
    'garbage with credentials' => ['http:///user:secret@', 'http or https URL'],
    'public with credentials' => ['http://user:secret@rpc.example.com', 'use https to reach rpc.example.com'],
]);

it('takes a label and cross-checks with an explorer', function (): void {
    $node = rpcNode(constructorArguments: ['label' => 'my node']);

    expect($node->describe())->toBe('my node');

    $agreeing = new CrossCheckingBlockHeaderSource($node, rpcNode());

    expect($agreeing->blockHeader(967_571)->hashHex())->toBe(BLOCK_HASH_967571)
        ->and($agreeing->describe())->toBe('my node and 127.0.0.1:8332 (cross-checked)');
});
