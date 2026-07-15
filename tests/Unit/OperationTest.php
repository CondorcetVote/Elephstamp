<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\{InvalidInputException, SerializationException};
use CondorcetVote\ElephStamp\Operation\{Append, HashOperation, Hexlify, Keccak256, Operation, Prepend, Reverse, Ripemd160, Sha1, Sha256};
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};

it('applies append and prepend', function (): void {
    expect(new Append('!')->apply('hi'))->toBe('hi!')
        ->and(new Prepend('>')->apply('hi'))->toBe('>hi');
});

it('applies reverse and hexlify', function (): void {
    expect((new Reverse)->apply('abc'))->toBe('cba')
        ->and((new Hexlify)->apply("\x00\xff"))->toBe('00ff');
});

it('hashes with the documented digest lengths and tags', function (HashOperation $operation, string $algorithm, int $length, string $tag): void {
    expect($operation->hashData('payload'))->toBe(hash($algorithm, 'payload', binary: true))
        ->and($operation->digestLength())->toBe($length)
        ->and($operation->tag())->toBe($tag);
})->with([
    'sha256' => [new Sha256, 'sha256', 32, "\x08"],
    'sha1' => [new Sha1, 'sha1', 20, "\x02"],
    'ripemd160' => [new Ripemd160, 'ripemd160', 20, "\x03"],
]);

it('hashes a stream identically to in-memory data', function (): void {
    $stream = fopen('php://temp', 'r+b');
    fwrite($stream, 'streamed payload');
    rewind($stream);

    expect((new Sha256)->hashStream($stream))->toBe(hash('sha256', 'streamed payload', binary: true));

    fclose($stream);
});

it('round-trips operations through serialization', function (Operation $operation): void {
    $serializer = new Serializer;
    $operation->serialize($serializer);

    $restored = Operation::deserialize(new Deserializer($serializer->getBytes()));

    expect($restored)->toEqual($operation);
})->with([
    'sha256' => [new Sha256],
    'append' => [new Append('suffix')],
    'prepend' => [new Prepend('prefix')],
    'reverse' => [new Reverse],
]);

it('rejects an unknown operation tag', function (): void {
    Operation::deserialize(new Deserializer("\x99"));
})->throws(SerializationException::class, 'Unknown operation tag');

it('rejects an empty binary operation argument', function (): void {
    new Append('');
})->throws(InvalidInputException::class);

it('rejects a message that is too long', function (): void {
    (new Sha256)->apply(str_repeat('a', Operation::MAX_MSG_LENGTH + 1));
})->throws(SerializationException::class, 'too long');

it('does not support applying keccak256 but recognises its tag', function (): void {
    $operation = Operation::deserialize(new Deserializer("\x67"));

    expect($operation)->toBeInstanceOf(Keccak256::class);

    $operation->apply('anything');
})->throws(SerializationException::class, 'Keccak-256');

it('rejects a non-hash operation where a hash is required', function (): void {
    $serializer = new Serializer;
    new Append('x')->serialize($serializer);

    HashOperation::deserializeHash(new Deserializer($serializer->getBytes()));
})->throws(SerializationException::class, 'cryptographic hash');

it('matches the known hash vectors for the empty message', function (): void {
    expect(bin2hex((new Sha256)->hashData('')))->toBe('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855')
        ->and(bin2hex((new Ripemd160)->hashData('')))->toBe('9c1185a5c5e9fc54612808977ee8f548b2258d31');
});

it('rejects a binary operation argument longer than the result limit', function (): void {
    new Append(str_repeat('.', Operation::MAX_RESULT_LENGTH + 1));
})->throws(InvalidInputException::class);

it('enforces append message and result length limits at the boundary', function (): void {
    // 4095 bytes of message + 1 byte suffix = 4096, exactly the result limit.
    expect(new Append('.')->apply(str_repeat('.', 4095)))->toHaveLength(4096);

    // 4096 bytes of message would produce a 4097-byte result.
    expect(fn() => new Append('.')->apply(str_repeat('.', 4096)))
        ->toThrow(SerializationException::class);
});

it('enforces hexlify length limits', function (): void {
    expect((new Hexlify)->apply(str_repeat('.', 2048)))->toHaveLength(4096)
        ->and(fn() => (new Hexlify)->apply(str_repeat('.', 2049)))->toThrow(SerializationException::class)
        ->and(fn() => (new Hexlify)->apply(''))->toThrow(SerializationException::class);
});

it('orders operations by tag', function (): void {
    // SHA-1 (0x02) sorts before RIPEMD-160 (0x03).
    expect((new Sha1)->comparisonKey() < (new Ripemd160)->comparisonKey())->toBeTrue();
});
