<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

/**
 * Append a fixed suffix to the message.
 */
final class Append extends BinaryOperation
{
    public const string TAG = "\xf0";

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'append ' . bin2hex($this->argument);
    }

    protected function compute(string $message): string
    {
        return $message . $this->argument;
    }
}
