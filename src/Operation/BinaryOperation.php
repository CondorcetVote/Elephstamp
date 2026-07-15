<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Serialization\Serializer;

/**
 * An operation that combines the message with a fixed argument (append/prepend).
 */
abstract class BinaryOperation extends Operation
{
    public function __construct(public readonly string $argument)
    {
        if ($argument === '') {
            throw new InvalidInputException(\sprintf('%s argument cannot be empty', static::class));
        }

        if (\strlen($argument) > self::MAX_RESULT_LENGTH) {
            throw new InvalidInputException(\sprintf('%s argument too long: %d > %d', static::class, \strlen($argument), self::MAX_RESULT_LENGTH));
        }
    }

    public function comparisonKey(): string
    {
        return $this->tag() . $this->argument;
    }

    public function serialize(Serializer $serializer): void
    {
        parent::serialize($serializer);
        $serializer->writeVarbytes($this->argument);
    }
}
