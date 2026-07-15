<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

/**
 * Convert the message to its lower-case hexadecimal representation.
 */
final class Hexlify extends UnaryOperation
{
    public const string TAG = "\xf3";

    /**
     * Every invocation doubles the input size, so the message limit is half the
     * result limit.
     */
    public const int MAX_MSG_LENGTH = 2048;

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'hexlify';
    }

    protected function compute(string $message): string
    {
        return bin2hex($message);
    }
}
