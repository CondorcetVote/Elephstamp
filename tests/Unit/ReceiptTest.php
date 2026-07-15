<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\{InvalidInputException, SerializationException};
use CondorcetVote\ElephStamp\{ElephStamp, FileToStamp, Receipt};

it('rejects receipt bytes above the size cap before parsing', function (): void {
    Receipt::fromBytes(str_repeat("\x00", Receipt::MAX_RECEIPT_BYTES + 1));
})->throws(SerializationException::class, 'maximum size');

it('rejects an oversized receipt file before reading it', function (): void {
    $path = makeTempPath();
    file_put_contents($path, str_repeat("\x00", Receipt::MAX_RECEIPT_BYTES + 1));

    Receipt::fromPath($path);
})->throws(SerializationException::class, 'maximum size');

it('rejects an oversized receipt SplFileObject while reading it', function (): void {
    $path = makeTempPath();
    file_put_contents($path, str_repeat("\x00", Receipt::MAX_RECEIPT_BYTES + 1));

    Receipt::fromSplFileObject(new SplFileObject($path));
})->throws(SerializationException::class, 'maximum size');

it('loads a receipt through an SplFileObject', function (): void {
    $receipt = ElephStamp::fake()->stamp(FileToStamp::fromContent('spl'));
    $path = makeTempPath();
    $receipt->saveToPath($path);

    expect(Receipt::fromSplFileObject(new SplFileObject($path))->toBytes())->toBe($receipt->toBytes());
});

it('throws a clean exception when saving to an unwritable path', function (): void {
    ElephStamp::fake()->stamp(FileToStamp::fromContent('x'))
        ->saveToPath('/nonexistent-elephstamp-dir/receipt.ots');
})->throws(InvalidInputException::class, 'Unable to write');

it('overwrites an existing receipt in place', function (): void {
    $receipt = ElephStamp::fake()->stamp(FileToStamp::fromContent('overwrite'));
    $path = makeTempPath();

    file_put_contents($path, 'previous content');
    $receipt->saveToPath($path);

    expect(file_get_contents($path))->toBe($receipt->toBytes());
});
