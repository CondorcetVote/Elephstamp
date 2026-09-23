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

it('computes keccak256, the Ethereum variant rather than SHA3-256', function (string $message, string $expected): void {
    $operation = Operation::deserialize(new Deserializer("\x67"));

    expect($operation)->toBeInstanceOf(Keccak256::class)
        ->and($operation->isComputable())->toBeTrue()
        ->and(bin2hex($operation->apply($message)))->toBe($expected)
        ->and(bin2hex((new Keccak256)->hashData($message)))->toBe($expected)
        ->and($expected)->not->toBe(hash('sha3-256', $message))
        ->and((new Keccak256)->digestLength())->toBe(32)
        ->and((new Keccak256)->describe())->toBe('keccak256');
})->with([
    'empty' => ['', 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470'],
    'abc' => ['abc', '4e03657aea45a94fc7d47ba826c8d667c0d1e6e33a64a036ec44f58fa12d6c45'],
    'fox' => ['The quick brown fox jumps over the lazy dog', '4d741b6f1eb29cb2a9b9911c82f56fa8d73b04959d3d9d222895df6c0b28aa15'],
]);

it('hashes streams and chunks with keccak256 like in-memory data', function (): void {
    $payload = random_bytes(300);
    $stream = fopen('php://temp', 'r+b');
    fwrite($stream, $payload);
    rewind($stream);

    expect((new Keccak256)->hashStream($stream))->toBe((new Keccak256)->hashData($payload))
        ->and((new Keccak256)->hashChunks(str_split($payload, 7)))->toBe((new Keccak256)->hashData($payload));

    fclose($stream);
});

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

it('resolves hash operations by name', function (): void {
    expect(HashOperation::fromName('sha256'))->toBeInstanceOf(Sha256::class)
        ->and(HashOperation::fromName('SHA1'))->toBeInstanceOf(Sha1::class)
        ->and(HashOperation::fromName('ripemd160'))->toBeInstanceOf(Ripemd160::class)
        ->and(HashOperation::fromName('keccak256'))->toBeInstanceOf(Keccak256::class)
        ->and(HashOperation::names())->toBe(['sha256', 'sha1', 'ripemd160', 'keccak256'])
        ->and(fn() => HashOperation::fromName('md5'))->toThrow(InvalidInputException::class, 'Unknown hash operation "md5"; known: sha256, sha1, ripemd160, keccak256')
        ->and(fn() => HashOperation::fromName('sha3-256'))->toThrow(InvalidInputException::class);

    foreach (HashOperation::names() as $name) {
        expect(HashOperation::fromName($name)->describe())->toBe($name);
    }
});

it('orders operations by tag', function (): void {
    // SHA-1 (0x02) sorts before RIPEMD-160 (0x03).
    expect((new Sha1)->comparisonKey() < (new Ripemd160)->comparisonKey())->toBeTrue();
});
