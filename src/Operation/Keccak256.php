<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

use CondorcetVote\ElephStamp\Exception\SerializationException;

/**
 * Keccak-256, as used by Ethereum attestations.
 *
 * This library only supports the Bitcoin calendar workflow, and PHP ships no
 * Keccak-256 implementation (its {@see hash()} "sha3-256" uses different
 * padding). The tag is recognised so proofs that merely reference it can be
 * identified, but applying it is not supported.
 */
final class Keccak256 extends UnaryOperation
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

    protected function compute(string $message): string
    {
        throw new SerializationException('Keccak-256 operations are not supported by this library');
    }
}
