<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

/**
 * Reverse the bytes of the message.
 */
final class Reverse extends UnaryOperation
{
    public const string TAG = "\xf2";

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'reverse';
    }

    protected function compute(string $message): string
    {
        return strrev($message);
    }
}
