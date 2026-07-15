<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};

it('round-trips varuint values', function (int $value): void {
    $serializer = new Serializer;
    $serializer->writeVaruint($value);

    expect(new Deserializer($serializer->getBytes())->readVaruint())->toBe($value);
})->with([0, 1, 127, 128, 255, 256, 16_383, 16_384, 1_000_000, \PHP_INT_MAX]);

it('encodes varuint with LEB128 continuation bits', function (): void {
    $serializer = new Serializer;
    $serializer->writeVaruint(300);

    // 300 = 0b100101100 -> 0xAC 0x02
    expect($serializer->getBytes())->toBe("\xac\x02");
});

it('round-trips varbytes', function (): void {
    $serializer = new Serializer;
    $serializer->writeVarbytes('opentimestamps');

    $deserializer = new Deserializer($serializer->getBytes());

    expect($deserializer->readVarbytes(1000))->toBe('opentimestamps');
});

it('round-trips booleans', function (bool $value): void {
    $serializer = new Serializer;
    $serializer->writeBool($value);

    expect(new Deserializer($serializer->getBytes())->readBool())->toBe($value);
})->with([true, false]);

it('rejects a negative varuint', function (): void {
    (new Serializer)->writeVaruint(-1);
})->throws(SerializationException::class);

it('rejects an out-of-range uint8', function (): void {
    (new Serializer)->writeUint8(256);
})->throws(SerializationException::class);

it('rejects truncated reads', function (): void {
    new Deserializer("\x02")->readBytes(4);
})->throws(SerializationException::class, 'Tried to read');

it('rejects an invalid boolean byte', function (): void {
    new Deserializer("\x02")->readBool();
})->throws(SerializationException::class);

it('enforces the varbytes maximum length', function (): void {
    $serializer = new Serializer;
    $serializer->writeVarbytes('too long');

    new Deserializer($serializer->getBytes())->readVarbytes(3);
})->throws(SerializationException::class, 'maximum length');

it('detects a bad magic header', function (): void {
    new Deserializer('not the magic bytes here')->assertMagic('OpenTimestamps');
})->throws(SerializationException::class, 'magic');

it('detects trailing garbage', function (): void {
    $deserializer = new Deserializer("\x01\x02");
    $deserializer->readBytes(1);

    $deserializer->assertEof();
})->throws(SerializationException::class, 'Trailing garbage');
