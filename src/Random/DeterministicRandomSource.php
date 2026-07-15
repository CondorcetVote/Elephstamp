<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Random;

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * A reproducible source, backed by a {@see Randomizer} over a seeded engine.
 *
 * Not cryptographically secure — intended only for tests and the fake client,
 * where predictable nonces make proofs deterministic.
 */
final class DeterministicRandomSource implements RandomSource
{
    private readonly Randomizer $randomizer;

    public function __construct(string $seed = 'elephstamp')
    {
        // Xoshiro256** takes a fixed 32-byte string seed; derive it from the
        // caller's arbitrary-length seed.
        $this->randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', $seed, binary: true)));
    }

    public function bytes(int $length): string
    {
        if ($length < 1) {
            throw new InvalidInputException(\sprintf('Requested random byte count must be positive: %d', $length));
        }

        return $this->randomizer->getBytes($length);
    }
}
