<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\FileToStamp;
use CondorcetVote\ElephStamp\Operation\Sha256;

it('computes the same digest from content, path and file handle', function (): void {
    $content = 'the quick brown fox';
    $expected = hash('sha256', $content, binary: true);

    $path = makeTempPath();
    file_put_contents($path, $content);

    $operation = new Sha256;

    expect(FileToStamp::fromContent($content)->digest($operation))->toBe($expected)
        ->and(FileToStamp::fromPath($path)->digest($operation))->toBe($expected)
        ->and(FileToStamp::fromSplFileObject(new SplFileObject($path, 'rb'))->digest($operation))->toBe($expected);
});

it('streams a large file without loading it whole', function (): void {
    $path = makeTempPath();
    $handle = fopen($path, 'wb');
    $context = hash_init('sha256');

    for ($i = 0; $i < 32; ++$i) {
        $chunk = str_repeat(\chr($i), 100_000);
        fwrite($handle, $chunk);
        hash_update($context, $chunk);
    }

    fclose($handle);

    expect(FileToStamp::fromPath($path)->digest(new Sha256))->toBe(hash_final($context, binary: true));
});

it('defaults to using a nonce and toggles with the wither methods', function (): void {
    expect(FileToStamp::fromContent('x')->useNonce)->toBeTrue()
        ->and(FileToStamp::fromContent('x')->withoutNonce()->useNonce)->toBeFalse()
        ->and(FileToStamp::fromContent('x')->withoutNonce()->withNonce()->useNonce)->toBeTrue();
});

it('rejects a missing path', function (): void {
    FileToStamp::fromPath('/no/such/file/here.bin');
})->throws(InvalidInputException::class);

it('uses a precomputed digest verbatim', function (): void {
    $digest = hash('sha256', 'precomputed', binary: true);

    expect(FileToStamp::fromDigest($digest)->digest(new Sha256))->toBe($digest);
});

it('rejects an empty precomputed digest', function (): void {
    FileToStamp::fromDigest('');
})->throws(InvalidInputException::class);

it('rejects a precomputed digest whose length does not match the hash operation', function (): void {
    FileToStamp::fromDigest('too short')->digest(new Sha256);
})->throws(InvalidInputException::class, 'does not match');
