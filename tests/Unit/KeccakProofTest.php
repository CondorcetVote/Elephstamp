<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\{PendingAttestation, UnknownAttestation};
use CondorcetVote\ElephStamp\{DetachedTimestampFile, Timestamp};
use CondorcetVote\ElephStamp\Operation\{Keccak256, Sha256};

it('parses and round-trips a proof containing a keccak256 branch', function (): void {
    $digest = hash('sha256', 'keccak interop', binary: true);
    $root = new Timestamp($digest);

    // The ethereum-style branch: everything below keccak256 is unverifiable.
    $root->addOp(new Keccak256)
        ->addAttestation(new UnknownAttestation("\x01\x02\x03\x04\x05\x06\x07\x08", 'eth'));

    // The bitcoin-style branch stays fully usable next to it.
    $root->addOp(new Sha256)->addAttestation(new PendingAttestation('https://a.example'));

    $bytes = new DetachedTimestampFile(new Sha256, $root)->toBytes();
    $parsed = DetachedTimestampFile::fromBytes($bytes);

    expect($parsed->toBytes())->toBe($bytes);
});

it('marks the subtree below keccak256 as unverifiable and skips it on upgrade', function (): void {
    $digest = hash('sha256', 'unverifiable', binary: true);
    $root = new Timestamp($digest);

    $keccakChild = $root->addOp(new Keccak256);
    $keccakChild->addAttestation(new PendingAttestation('https://eth.example'));

    $root->addOp(new Sha256)->addAttestation(new PendingAttestation('https://btc.example'));

    $pending = $root->findPending();

    expect($keccakChild->msg)->toBeNull()
        ->and($pending)->toHaveCount(1)
        ->and($pending[0]['msg'])->toBe(hash('sha256', $digest, binary: true))
        ->and($pending[0]['attestation']->uri)->toBe('https://btc.example');
});
