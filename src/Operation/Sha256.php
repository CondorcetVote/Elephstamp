<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

/**
 * SHA-256, the default and recommended hash operation.
 */
final class Sha256 extends HashOperation
{
    public const string TAG = "\x08";

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'sha256';
    }

    public function digestLength(): int
    {
        return 32;
    }

    protected function algorithm(): string
    {
        return 'sha256';
    }
}
