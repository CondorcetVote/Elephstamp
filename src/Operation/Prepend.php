<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

/**
 * Prepend a fixed prefix to the message.
 */
final class Prepend extends BinaryOperation
{
    public const TAG = "\xf1";

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'prepend ' . bin2hex($this->argument);
    }

    protected function compute(string $message): string
    {
        return $this->argument . $message;
    }
}
