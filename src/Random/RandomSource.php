<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Random;

/**
 * Source of the random bytes used as per-file nonces.
 *
 * Abstracted so tests and the fake client can inject a deterministic source
 * and get reproducible proofs.
 */
interface RandomSource
{
    /**
     * Return exactly $length random bytes.
     */
    public function bytes(int $length): string;
}
