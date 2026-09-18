<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\BlockSourceException;
use CondorcetVote\ElephStamp\Verify\BlockHeader;

/**
 * The genuine header of Bitcoin block 967571, as returned by mempool.space.
 */
const HEADER_967571 = '0060b62931171a874b5c03a881e68c7c0b273d237aae1cd0f04100000000000000000000abbfd07a03a3484f2abe54f23c20bbd9f33adb0dfcb4f19394be9f8e271d8f097e58ad6a5e3502171a8030e6';

it('parses a genuine header, recomputing its hash and checking its proof of work', function (): void {
    $header = BlockHeader::fromRawHeader(967_571, hex2bin(HEADER_967571));

    expect($header->height)->toBe(967_571)
        ->and($header->hashHex())->toBe('00000000000000000000e32d9a295b892cdf1dcbdeb52461fb0f5e32cefa8364')
        ->and($header->merkleRootHex())->toBe('098f1d278e9fbe9493f1b4fc0ddb3af3d9bb203cf254be2a4f48a3037ad0bfab')
        ->and($header->time->format(\DATE_ATOM))->toBe('2026-09-18T15:27:58+00:00')
        ->and($header->rawHeader)->toBe(hex2bin(HEADER_967571))
        ->and(bin2hex($header->merkleRoot))->toBe('abbfd07a03a3484f2abe54f23c20bbd9f33adb0dfcb4f19394be9f8e271d8f09');
});

it('rejects a header whose proof of work does not hold', function (): void {
    // Flip one bit of the merkle root: the hash changes and no longer meets the target.
    $tampered = hex2bin(HEADER_967571);
    $tampered[36] = \chr(\ord($tampered[36]) ^ 0x01);

    BlockHeader::fromRawHeader(967_571, $tampered);
})->throws(BlockSourceException::class, 'proof of work');

it('rejects a header of the wrong length', function (): void {
    BlockHeader::fromRawHeader(1, substr(hex2bin(HEADER_967571), 0, 79));
})->throws(BlockSourceException::class, '80 bytes');

it('accepts the genesis block header', function (): void {
    $genesis = '0100000000000000000000000000000000000000000000000000000000000000000000003ba3edfd7a7b12b27ac72c3e67768f617fc81bc3888a51323a9fb8aa4b1e5e4a29ab5f49ffff001d1dac2b7c';

    $header = BlockHeader::fromRawHeader(0, hex2bin($genesis));

    expect($header->hashHex())->toBe('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f')
        ->and($header->merkleRootHex())->toBe('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b');
});

it('validates the sizes of a hand-built header', function (): void {
    new BlockHeader(1, 'short', str_repeat("\0", 32), new DateTimeImmutable);
})->throws(BlockSourceException::class, '32 bytes');
