<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\PendingAttestation;
use CondorcetVote\ElephStamp\{DetachedTimestampFile, Timestamp};
use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Operation\Sha256;

const EMPTY_SHA256 = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

function pendingBytes(string $uri): string
{
    return hex2bin('83dfe30d2ef90c8e') . \chr(\strlen($uri) + 1) . \chr(\strlen($uri)) . $uri;
}

it('serializes a detached timestamp to the exact expected bytes', function (): void {
    $timestamp = new Timestamp(hex2bin(EMPTY_SHA256));
    $timestamp->addAttestation(new PendingAttestation('foobar'));
    $detached = new DetachedTimestampFile(new Sha256, $timestamp);

    $expected = DetachedTimestampFile::HEADER_MAGIC
        . "\x01"                       // major version
        . "\x08" . hex2bin(EMPTY_SHA256) // sha256 file-hash op + digest
        . "\x00" . pendingBytes('foobar');

    expect($detached->toBytes())->toBe($expected)
        ->and(DetachedTimestampFile::fromBytes($expected)->fileDigest())->toBe(hex2bin(EMPTY_SHA256));
});

it('rejects malformed detached timestamps', function (string $bytes): void {
    DetachedTimestampFile::fromBytes($bytes);
})->throws(SerializationException::class)->with([
    'empty' => [''],
    'bad magic' => ["\x00Not a OpenTimestamps Proof \x00\xbf\x89\xe2\xe8\x84\xe8\x92\x94\x01"],
    'unsupported version' => [DetachedTimestampFile::HEADER_MAGIC . "\x00"],
    'invalid opcode' => [DetachedTimestampFile::HEADER_MAGIC . "\x01" . "\x42" . str_repeat("\x00", 32) . "\x00" . 'PENDING'],
    'trailing garbage' => [
        DetachedTimestampFile::HEADER_MAGIC . "\x01" . "\x08" . hex2bin(EMPTY_SHA256)
        . "\x00" . pendingBytes('foobar') . 'trailing garbage',
    ],
]);
