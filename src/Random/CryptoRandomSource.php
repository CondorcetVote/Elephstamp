<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Random;

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use Random\Engine\Secure as SecureEngine;
use Random\Randomizer;

/**
 * The default source, backed by a {@see Randomizer} over the cryptographically
 * secure engine.
 */
final class CryptoRandomSource implements RandomSource
{
    private readonly Randomizer $randomizer;

    public function __construct()
    {
        $this->randomizer = new Randomizer(new SecureEngine);
    }

    public function bytes(int $length): string
    {
        if ($length < 1) {
            throw new InvalidInputException(\sprintf('Requested random byte count must be positive: %d', $length));
        }

        return $this->randomizer->getBytes($length);
    }
}
