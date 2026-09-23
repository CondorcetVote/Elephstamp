<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

/**
 * RIPEMD-160.
 */
final class Ripemd160 extends NativeHashOperation
{
    public const string TAG = "\x03";

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'ripemd160';
    }

    public function digestLength(): int
    {
        return 20;
    }

    protected function algorithm(): string
    {
        return 'ripemd160';
    }
}
