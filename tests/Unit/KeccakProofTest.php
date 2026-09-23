<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\{PendingAttestation, UnknownAttestation};
use CondorcetVote\ElephStamp\Operation\{Keccak256, Sha256};
use CondorcetVote\ElephStamp\Random\DeterministicRandomSource;
use CondorcetVote\ElephStamp\{DetachedTimestampFile, ElephStamp, FileToStamp, Receipt, Timestamp};
use kornrunner\Keccak;

it('parses and round-trips a proof containing a keccak256 branch', function (): void {
    $digest = hash('sha256', 'keccak interop', binary: true);
    $root = new Timestamp($digest);

    // The ethereum-style branch, next to a bitcoin-style one.
    $root->addOp(new Keccak256)
        ->addAttestation(new UnknownAttestation("\x01\x02\x03\x04\x05\x06\x07\x08", 'eth'));

    // The bitcoin-style branch stays fully usable next to it.
    $root->addOp(new Sha256)->addAttestation(new PendingAttestation('https://a.example'));

    $bytes = new DetachedTimestampFile(new Sha256, $root)->toBytes();
    $parsed = DetachedTimestampFile::fromBytes($bytes);

    expect($parsed->toBytes())->toBe($bytes);
});

it('computes the messages below a keccak256 edge and polls its calendar', function (): void {
    $digest = hash('sha256', 'computable', binary: true);
    $root = new Timestamp($digest);

    $keccakChild = $root->addOp(new Keccak256);
    $keccakChild->addAttestation(new PendingAttestation('https://eth.example'));

    $root->addOp(new Sha256)->addAttestation(new PendingAttestation('https://btc.example'));

    $pending = $root->findPending();

    expect($keccakChild->msg)->toBe(Keccak::hash($digest, 256, raw_output: true))
        ->and($pending)->toHaveCount(2)
        ->and(array_column(array_column($pending, 'attestation'), 'uri'))->toBe(['https://eth.example', 'https://btc.example']);
});

it('reads and verifies a proof whose file hash is keccak256', function (): void {
    $content = 'a file hashed the ethereum way';
    $digest = Keccak::hash($content, 256, raw_output: true);

    $client = ElephStamp::fake();
    $stamped = new ElephStamp(
        calendarClient: $client->fakeCalendar(),
        calendarUrls: [ElephStamp::FAKE_CALENDAR_URL],
        hashOperation: new Keccak256,
        randomSource: new DeterministicRandomSource,
        upgradeWhitelist: [],
        blockHeaderSource: $client->fakeBlockSource(),
    );

    $receipt = $stamped->stamp(FileToStamp::fromContent($content));
    $client->fakeCalendar()->confirmAll();
    $stamped->upgrade($receipt);

    $reloaded = Receipt::fromBytes($receipt->toBytes());

    expect($reloaded->toBytes())->toBe($receipt->toBytes())
        ->and($reloaded->hashOperation())->toBeInstanceOf(Keccak256::class)
        ->and($reloaded->fileDigest())->toBe($digest)
        ->and($stamped->verify($reloaded, FileToStamp::fromContent($content))->isVerified())->toBeTrue()
        ->and($stamped->verify($reloaded, FileToStamp::fromDigest($digest))->isVerified())->toBeTrue()
        ->and($stamped->verify($reloaded, FileToStamp::fromContent('other'))->isVerified())->toBeFalse();
});
