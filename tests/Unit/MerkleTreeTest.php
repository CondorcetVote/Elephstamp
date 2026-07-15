<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Merkle\MerkleTree;
use CondorcetVote\ElephStamp\Timestamp;

it('computes cat_sha256 for known inputs', function (): void {
    $leftRight = MerkleTree::concatenateThenSha256(new Timestamp('foo'), new Timestamp('bar'));

    expect(bin2hex($leftRight->msg))->toBe('c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2');

    $righter = MerkleTree::concatenateThenSha256($leftRight, new Timestamp('baz'));

    expect(bin2hex($righter->msg))->toBe('23388b16c66f1fa37ef14af8eb081712d570813e2afb8c8ae86efa726f3b7276');
});

it('builds the known merkle roots', function (int $count, string $expectedRoot): void {
    $leaves = array_map(static fn(int $i): Timestamp => new Timestamp(\chr($i)), range(0, $count - 1));

    expect(bin2hex(MerkleTree::build($leaves)->msg))->toBe($expectedRoot);
})->with([
    [1, '00'],
    [2, 'b413f47d13ee2fe6c845b2ee141af81de858df4ec549a58b7970bb96645bc8d2'],
    [3, 'e6aa639123d8aac95d13d365ec3779dade4b49c083a8fed97d7bfc0d89bb6a5e'],
    [4, '7699a4fdd6b8b6908a344f73b8f05c8e1400f7253f544602c442ff5c65504b24'],
    [5, 'aaa9609d0c949fee22c1c941a4432f32dc1c2de939e4af25207f0dc62df0dbd8'],
    [6, 'ebdb4245f648b7e77b60f4f8a99a6d0529d1d372f98f35478b3284f16da93c06'],
    [7, 'ba4603a311279dea32e8958bfb660c86237157bf79e6bfee857803e811d91b8f'],
]);

it('needs at least one leaf', function (): void {
    MerkleTree::build([]);
})->throws(CondorcetVote\ElephStamp\Exception\InvalidInputException::class);
