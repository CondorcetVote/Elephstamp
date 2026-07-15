<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation, TimeAttestation, UnknownAttestation};
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};
use CondorcetVote\ElephStamp\Exception\SerializationException;

function roundTripAttestation(TimeAttestation $attestation): TimeAttestation
{
    $serializer = new Serializer;
    $attestation->serialize($serializer);

    return TimeAttestation::deserialize(new Deserializer($serializer->getBytes()));
}

it('round-trips a pending attestation', function (): void {
    $attestation = new PendingAttestation('https://a.pool.opentimestamps.org');

    expect(roundTripAttestation($attestation))->toEqual($attestation);
});

it('round-trips a bitcoin attestation', function (): void {
    $attestation = new BitcoinAttestation(812_345);

    $restored = roundTripAttestation($attestation);

    expect($restored)->toBeInstanceOf(BitcoinAttestation::class)
        ->and($restored->blockHeight)->toBe(812_345);
});

it('preserves an unknown attestation verbatim', function (): void {
    $serializer = new Serializer;
    // A tag this library does not know about, with an arbitrary payload.
    $serializer->writeBytes("\x01\x02\x03\x04\x05\x06\x07\x08");
    $serializer->writeVarbytes('opaque-payload');
    $bytes = $serializer->getBytes();

    $attestation = TimeAttestation::deserialize(new Deserializer($bytes));

    expect($attestation)->toBeInstanceOf(UnknownAttestation::class);

    $out = new Serializer;
    $attestation->serialize($out);

    expect($out->getBytes())->toBe($bytes);
});

it('rejects a calendar URI with illegal characters', function (): void {
    new PendingAttestation('https://evil.example/?query=1');
})->throws(SerializationException::class);

it('orders attestations by tag across types, then by their own criteria', function (): void {
    $bitcoin = new BitcoinAttestation(5);
    $pending = new PendingAttestation('https://z.example');

    // Bitcoin's tag (0x05...) sorts before pending's (0x83...).
    expect($bitcoin->compareTo($pending))->toBeLessThan(0)
        ->and($pending->compareTo($bitcoin))->toBeGreaterThan(0)
        ->and(new BitcoinAttestation(1)->compareTo(new BitcoinAttestation(2)))->toBeLessThan(0)
        ->and(new UnknownAttestation('unknown1', '')->compareTo(new UnknownAttestation('unknown2', '')))->toBeLessThan(0);
});

it('serializes a pending attestation to the exact expected bytes', function (): void {
    $serializer = new Serializer;
    new PendingAttestation('foobar')->serialize($serializer);

    expect($serializer->getBytes())->toBe(hex2bin('83dfe30d2ef90c8e' . '07' . '06') . 'foobar');
});

it('accepts a 1000-byte URI but rejects 1001 bytes on deserialization', function (): void {
    $ok = hex2bin('83dfe30d2ef90c8e' . 'ea07' . 'e807') . str_repeat('x', 1000);
    expect(TimeAttestation::deserialize(new Deserializer($ok)))->toBeInstanceOf(PendingAttestation::class);

    $tooLong = hex2bin('83dfe30d2ef90c8e' . 'eb07' . 'e907') . str_repeat('x', 1001);
    expect(fn() => TimeAttestation::deserialize(new Deserializer($tooLong)))->toThrow(SerializationException::class);
});

it('rejects trailing garbage inside a pending attestation payload', function (): void {
    // Outer length says 8 bytes, but the URI is only 6 + 1 stray byte.
    $bytes = hex2bin('83dfe30d2ef90c8e' . '08' . '06') . 'foobarx';

    TimeAttestation::deserialize(new Deserializer($bytes));
})->throws(SerializationException::class);

it('rejects trailing garbage inside a bitcoin attestation payload', function (): void {
    $bytes = hex2bin('0588960d73d71901' . '02' . '00' . 'ff');

    TimeAttestation::deserialize(new Deserializer($bytes));
})->throws(SerializationException::class);

it('rejects an attestation payload larger than the maximum', function (): void {
    // Declared payload length 8193 exceeds MAX_PAYLOAD_SIZE.
    $bytes = hex2bin('0102030405060708') . "\x81\x40" . str_repeat('x', 8193);

    TimeAttestation::deserialize(new Deserializer($bytes));
})->throws(SerializationException::class);
