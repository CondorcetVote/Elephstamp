<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\UnknownAttestation;
use CondorcetVote\ElephStamp\Exception\SerializationException;
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

it('rejects every truncation of a reference .ots file', function (): void {
    $bytes = file_get_contents(__DIR__ . '/../fixtures/hello-world.txt.ots');

    for ($length = \strlen($bytes) - 1; $length >= 0; --$length) {
        try {
            DetachedTimestampFile::fromBytes(substr($bytes, 0, $length));
            $this->fail(\sprintf('Truncation to %d bytes was accepted', $length));
        } catch (SerializationException) {
            // Every truncation must fail with the library's own exception:
            // never a PHP error, a hang, or a silent success.
        }
    }

    expect(true)->toBeTrue();
});

it('reads the exact semantic content of the reference fixtures', function (): void {
    $complete = Receipt::fromPath(__DIR__ . '/../fixtures/hello-world.txt.ots');
    $twoCalendars = Receipt::fromPath(__DIR__ . '/../fixtures/two-calendars.txt.ots');

    expect($complete->bitcoinBlockHeight())->toBe(358_391)
        ->and($twoCalendars->pendingCalendarUris())->toBe([
            'https://alice.btc.calendar.opentimestamps.org',
            'https://bob.btc.calendar.opentimestamps.org',
        ]);
});

it('preserves an unknown notary attestation alongside the known one', function (): void {
    $receipt = Receipt::fromPath(__DIR__ . '/../fixtures/known-and-unknown-notary.txt.ots');

    $unknown = array_filter(
        $receipt->detachedTimestampFile()->timestamp->allAttestations(),
        static fn(array $entry): bool => $entry['attestation'] instanceof UnknownAttestation,
    );

    expect($unknown)->toHaveCount(1)
        ->and($receipt->pendingCalendarUris())->toBe(['https://bob.btc.calendar.opentimestamps.org']);
});

it('shares one merkle commitment across the batch fixtures', function (): void {
    $commitments = [];

    foreach ([1, 2, 3] as $i) {
        $receipt = Receipt::fromPath(__DIR__ . \sprintf('/../fixtures/merkle%d.txt.ots', $i));
        $commitments[] = array_map(
            static fn(array $entry): string => bin2hex($entry['msg']),
            $receipt->detachedTimestampFile()->timestamp->findPending(),
        );
    }

    // The three files were stamped as one batch by the reference client, so
    // their proofs must converge on the same calendar commitments.
    expect($commitments[0])->not->toBeEmpty()
        ->and($commitments[1])->toBe($commitments[0])
        ->and($commitments[2])->toBe($commitments[0]);
});
