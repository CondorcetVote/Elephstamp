<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\{DetachedTimestampFile, Receipt};

/**
 * These fixtures are genuine `.ots` files produced by the reference
 * OpenTimestamps client. Deserializing then re-serializing them must yield
 * byte-identical output, which proves format interoperability.
 */
$fixtures = glob(__DIR__ . '/../fixtures/*.ots');

it('round-trips reference .ots files byte-for-byte', function (string $path): void {
    $bytes = file_get_contents($path);

    $detached = DetachedTimestampFile::fromBytes($bytes);

    expect($detached->toBytes())->toBe($bytes);
})->with(array_map(static fn(string $p): array => [$p], $fixtures ?: []));

it('reports completeness of reference fixtures', function (): void {
    $complete = Receipt::fromPath(__DIR__ . '/../fixtures/hello-world.txt.ots');
    $pending = Receipt::fromPath(__DIR__ . '/../fixtures/incomplete.txt.ots');

    expect($complete->isComplete())->toBeTrue()
        ->and($complete->bitcoinBlockHeight())->toBeInt()
        ->and($pending->isPending())->toBeTrue()
        ->and($pending->bitcoinBlockHeight())->toBeNull()
        ->and($pending->pendingCalendarUris())->not->toBeEmpty();
});

it('computes the file digest matching the timestamped file', function (): void {
    $receipt = Receipt::fromPath(__DIR__ . '/../fixtures/hello-world.txt.ots');

    expect($receipt->fileDigestHex())->toBe(hash('sha256', file_get_contents(__DIR__ . '/../fixtures/hello-world.txt')));
});
