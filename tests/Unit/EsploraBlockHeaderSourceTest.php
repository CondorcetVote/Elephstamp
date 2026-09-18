<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\{BlockSourceException, InvalidInputException};
use CondorcetVote\ElephStamp\Verify\{CrossCheckingBlockHeaderSource, EsploraBlockHeaderSource, Explorer, FakeBlockHeaderSource};
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * A mock Esplora answering like mempool.space did for block 967571.
 *
 * @param array<string, MockResponse|string> $overrides responses keyed by URL path, to break one answer
 */
function esplora(array $overrides = [], array &$requests = []): EsploraBlockHeaderSource
{
    $client = new MockHttpClient(function (string $method, string $url) use ($overrides, &$requests): MockResponse {
        $requests[] = $url;
        $path = substr($url, \strlen('https://esplora.test/api/'));

        if (isset($overrides[$path])) {
            return $overrides[$path] instanceof MockResponse ? $overrides[$path] : new MockResponse($overrides[$path]);
        }

        return match ($path) {
            'block-height/967571' => new MockResponse(BLOCK_HASH_967571),
            'block/' . BLOCK_HASH_967571 . '/header' => new MockResponse(HEADER_967571),
            'blocks/tip/height' => new MockResponse('967620'),
            default => new MockResponse('', ['http_code' => 404]),
        };
    });

    return new EsploraBlockHeaderSource('https://esplora.test/api/', $client, label: 'test explorer');
}

it('fetches the hash then the raw header, and checks they agree', function (): void {
    $requests = [];
    $source = esplora(requests: $requests);

    $header = $source->blockHeader(967_571);

    expect($header->hashHex())->toBe(BLOCK_HASH_967571)
        ->and($header->merkleRootHex())->toBe('098f1d278e9fbe9493f1b4fc0ddb3af3d9bb203cf254be2a4f48a3037ad0bfab')
        ->and($source->tipHeight())->toBe(967_620)
        ->and($source->describe())->toBe('test explorer')
        ->and($requests)->toBe([
            'https://esplora.test/api/block-height/967571',
            'https://esplora.test/api/block/' . BLOCK_HASH_967571 . '/header',
            'https://esplora.test/api/blocks/tip/height',
        ]);
});

it('reports an unknown block', function (): void {
    esplora()->blockHeader(1);
})->throws(BlockSourceException::class, 'does not know the block 1');

it('rejects a header that does not hash to the announced block hash', function (): void {
    // The genuine header of another block: valid proof of work, wrong hash.
    $genesis = '0100000000000000000000000000000000000000000000000000000000000000000000003ba3edfd7a7b12b27ac72c3e67768f617fc81bc3888a51323a9fb8aa4b1e5e4a29ab5f49ffff001d1dac2b7c';

    esplora(['block/' . BLOCK_HASH_967571 . '/header' => $genesis])->blockHeader(967_571);
})->throws(BlockSourceException::class, 'does not match the announced block hash');

it('rejects malformed answers', function (string $path, string $body, string $message): void {
    $source = esplora([$path => $body]);

    try {
        $path === 'blocks/tip/height' ? $source->tipHeight() : $source->blockHeader(967_571);
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toContain($message);
    }
})->with([
    'bad hash' => ['block-height/967571', 'not-a-hash', 'invalid hash'],
    'bad header' => ['block/' . BLOCK_HASH_967571 . '/header', 'abcd', 'invalid header'],
    'bad tip' => ['blocks/tip/height', 'soon', 'invalid chain tip'],
    'oversized' => ['blocks/tip/height', str_repeat('9', 2_000), 'size limit'],
]);

it('reports transport errors and unexpected statuses', function (): void {
    $source = esplora(['blocks/tip/height' => new MockResponse('', ['http_code' => 503])]);

    try {
        $source->tipHeight();
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toContain('status 503');
    }

    $down = new EsploraBlockHeaderSource('https://down.test', new MockHttpClient(static fn() => throw new Symfony\Component\HttpClient\Exception\TransportException('connection refused')));

    try {
        $down->tipHeight();
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toContain('connection refused')
            ->and($down->describe())->toBe('down.test');
    }
});

it('refuses a non-https explorer URL', function (): void {
    new EsploraBlockHeaderSource('http://esplora.internal/api');
})->throws(InvalidInputException::class, 'https');

it('knows the public explorers and builds sources for them', function (): void {
    expect(Explorer::DEFAULT)->toBe(Explorer::MempoolSpace)
        ->and(Explorer::MempoolSpace->baseUrl())->toBe('https://mempool.space/api')
        ->and(Explorer::Blockstream->baseUrl())->toBe('https://blockstream.info/api')
        ->and(Explorer::Blockstream->source()->describe())->toBe('blockstream.info')
        ->and(Explorer::MempoolSpace->source(timeout: 2.0)->describe())->toBe('mempool.space')
        ->and(Explorer::from('mempool'))->toBe(Explorer::MempoolSpace);
});

it('cross-checks several sources and fails when they disagree', function (): void {
    $a = esplora();
    $b = esplora();

    $agreeing = new CrossCheckingBlockHeaderSource($a, $b);

    expect($agreeing->blockHeader(967_571)->hashHex())->toBe(BLOCK_HASH_967571)
        ->and($agreeing->tipHeight())->toBe(967_620)
        ->and($agreeing->describe())->toBe('test explorer and test explorer (cross-checked)');

    $fake = new FakeBlockHeaderSource;
    $fake->addBlock(967_571, str_repeat("\x11", 32));
    $fake->setTipHeight(967_600);

    $disagreeing = new CrossCheckingBlockHeaderSource($a, $fake);

    expect($disagreeing->tipHeight())->toBe(967_600);

    try {
        $disagreeing->blockHeader(967_571);
        $this->fail('No exception thrown');
    } catch (BlockSourceException $exception) {
        expect($exception->getMessage())->toContain('disagree about block 967571')
            ->toContain('fake block source');
    }
});

it('needs at least two sources to cross-check', function (): void {
    new CrossCheckingBlockHeaderSource(esplora());
})->throws(InvalidInputException::class);
