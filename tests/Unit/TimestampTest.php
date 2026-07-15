<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Operation\{Append, Sha256};
use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};
use CondorcetVote\ElephStamp\Timestamp;

it('serializes and deserializes a timestamp for its message', function (): void {
    $timestamp = new Timestamp('digest');
    $child = $timestamp->addOp(new Append('!'));
    $child->addAttestation(new PendingAttestation('https://a.example'));

    $serializer = new Serializer;
    $timestamp->serialize($serializer);

    $restored = Timestamp::deserialize(new Deserializer($serializer->getBytes()), 'digest');

    $out = new Serializer;
    $restored->serialize($out);

    expect($out->getBytes())->toBe($serializer->getBytes());
});

it('refuses to serialize an empty timestamp', function (): void {
    new Timestamp('x')->serialize(new Serializer);
})->throws(SerializationException::class, 'empty timestamp');

it('deduplicates identical operations', function (): void {
    $timestamp = new Timestamp('msg');
    $first = $timestamp->addOp(new Append('a'));
    $second = $timestamp->addOp(new Append('a'));

    expect($first)->toBe($second)
        ->and($timestamp->operations())->toHaveCount(1);
});

it('merges attestations and operations from another timestamp', function (): void {
    $a = new Timestamp('msg');
    $a->addAttestation(new PendingAttestation('https://a.example'));

    $b = new Timestamp('msg');
    $b->addAttestation(new BitcoinAttestation(700_000));

    $a->merge($b);

    expect($a->attestations())->toHaveCount(2)
        ->and($a->hasBitcoinAttestation())->toBeTrue();
});

it('refuses to merge timestamps for different messages', function (): void {
    new Timestamp('one')->merge(new Timestamp('two'));
})->throws(SerializationException::class, 'different messages');

it('finds the shallowest pending attestations', function (): void {
    $timestamp = new Timestamp('digest');
    $leaf = $timestamp->addOp(new Sha256);
    $leaf->addAttestation(new PendingAttestation('https://a.example'));

    $pending = $timestamp->findPending();

    expect($pending)->toHaveCount(1)
        ->and($pending[0]['node'])->toBe($leaf)
        ->and($pending[0]['attestation']->uri)->toBe('https://a.example');
});

it('reports every attestation with its committed message', function (): void {
    $timestamp = new Timestamp('digest');
    $timestamp->addAttestation(new PendingAttestation('https://a.example'));
    $child = $timestamp->addOp(new Append('!'));
    $child->addAttestation(new BitcoinAttestation(1));

    expect($timestamp->allAttestations())->toHaveCount(2);
});

it('serializes multiple sorted attestations and a nested op to exact bytes', function (): void {
    $pending = static fn(string $uri): string => hex2bin('83dfe30d2ef90c8e') . \chr(\strlen($uri) + 1) . \chr(\strlen($uri)) . $uri;

    $timestamp = new Timestamp('foo');
    $timestamp->addAttestation(new PendingAttestation('foobar'));
    $timestamp->addAttestation(new PendingAttestation('barfoo'));
    $timestamp->addAttestation(new PendingAttestation('foobaz'));
    $timestamp->addOp(new Sha256)->addAttestation(new PendingAttestation('deeper'));

    $serializer = new Serializer;
    $timestamp->serialize($serializer);

    // Attestations sort barfoo < foobar < foobaz; the sha256 op (0x08) comes last.
    $expected = "\xff\x00" . $pending('barfoo')
        . "\xff\x00" . $pending('foobar')
        . "\xff\x00" . $pending('foobaz')
        . "\x08\x00" . $pending('deeper');

    expect($serializer->getBytes())->toBe($expected)
        ->and(Timestamp::deserialize(new Deserializer($expected), 'foo'))->toEqual($timestamp);
});

it('fails to bind an op result timestamp for the wrong message', function (): void {
    new Timestamp('abcd')->setOp(new Sha256, new Timestamp('wrong'));
})->throws(SerializationException::class);

it('rejects a timestamp that exceeds the recursion limit', function (): void {
    $serialized = str_repeat("\x08", 256) . "\x00" . hex2bin('83dfe30d2ef90c8e' . '07' . '06') . 'barfoo';

    Timestamp::deserialize(new Deserializer($serialized), '');
})->throws(SerializationException::class, 'recursion');

it('rejects a deserialized op whose result exceeds the length limit', function (): void {
    // OpAppend(0x00) then a pending attestation.
    $serialized = "\xf0\x01\x00" . "\x00" . hex2bin('83dfe30d2ef90c8e' . '07' . '06') . 'barfoo';

    // 4095-byte message -> 4096-byte result, still valid.
    expect(Timestamp::deserialize(new Deserializer($serialized), str_repeat('.', 4095)))->toBeInstanceOf(Timestamp::class);

    // 4096-byte message -> 4097-byte result, too long.
    expect(fn() => Timestamp::deserialize(new Deserializer($serialized), str_repeat('.', 4096)))
        ->toThrow(SerializationException::class);
});
