<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use kornrunner\Keccak;

/**
 * Keccak-256, the original Keccak as used by Ethereum.
 *
 * This is not SHA3-256: the two share the permutation but pad differently, so
 * PHP's `hash('sha3-256')` gives another result. The digest comes from the
 * pure-PHP `kornrunner/keccak` package, which has no incremental interface:
 * hashing a file with it holds the whole content in memory, and runs at pure
 * PHP speed (about a megabyte per second). Proofs use it as an edge; as a
 * file hash it is here for interoperability, not recommended for large files.
 */
final class Keccak256 extends HashOperation
{
    public const string TAG = "\x67";

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'keccak256';
    }

    public function digestLength(): int
    {
        return 32;
    }

    public function hashData(string $data): string
    {
        return Keccak::hash($data, 256, raw_output: true);
    }

    public function hashStream($stream): string
    {
        $data = stream_get_contents($stream);

        if ($data === false) {
            throw new InvalidInputException('Failed while reading the stream to hash');
        }

        return $this->hashData($data);
    }

    public function hashChunks(iterable $chunks): string
    {
        $data = '';

        foreach ($chunks as $chunk) {
            $data .= $chunk;
        }

        return $this->hashData($data);
    }
}
