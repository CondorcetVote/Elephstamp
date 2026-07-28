<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

use CondorcetVote\ElephStamp\Exception\SerializationException;

/**
 * Keccak-256, as used by Ethereum attestations.
 *
 * This library only supports the Bitcoin calendar workflow, and PHP ships no
 * Keccak-256 implementation (its {@see hash()} "sha3-256" uses different
 * padding). Proofs containing a keccak256 edge still deserialize and
 * round-trip byte-identically, but the subtree below the edge is marked
 * unverifiable: its messages are unknown, so it can be neither verified nor
 * upgraded.
 */
final class Keccak256 extends UnaryOperation
{
    public const TAG = "\x67";

    public function tag(): string
    {
        return self::TAG;
    }

    public function isComputable(): bool
    {
        return false;
    }

    public function describe(): string
    {
        return 'keccak256';
    }

    protected function compute(string $message): string
    {
        throw new SerializationException('Keccak-256 operations are not supported by this library');
    }
}
