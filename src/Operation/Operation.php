<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};

/**
 * A single edge in a timestamp proof tree.
 *
 * An operation takes a message and produces a result, which becomes the
 * message of the next node in the tree. Operations are immutable value objects.
 */
abstract class Operation
{
    /**
     * Maximum length of an operation result.
     *
     * Bounds the memory a verifier needs to walk a single commitment path.
     */
    public const int MAX_RESULT_LENGTH = 4096;

    /**
     * Maximum length of a message an operation may be applied to.
     */
    public const int MAX_MSG_LENGTH = 4096;

    /**
     * The one-byte tag identifying this operation in the binary format.
     */
    abstract public function tag(): string;

    /**
     * Human-readable label for the operation, used when describing a proof.
     */
    abstract public function describe(): string;

    /**
     * Compute the operation result for the given message.
     *
     * @throws SerializationException if the message or result violates the length limits
     */
    final public function apply(string $message): string
    {
        if (\strlen($message) > static::MAX_MSG_LENGTH) {
            throw new SerializationException(\sprintf('Message too long for %s: %d > %d', static::class, \strlen($message), static::MAX_MSG_LENGTH));
        }

        $result = $this->compute($message);

        // No operation may produce an empty result: that would trivially allow
        // a cycle in the commitment graph.
        if ($result === '') {
            throw new SerializationException(\sprintf('%s produced an empty result', static::class));
        }

        if (\strlen($result) > static::MAX_RESULT_LENGTH) {
            throw new SerializationException(\sprintf('Result too long for %s: %d > %d', static::class, \strlen($result), static::MAX_RESULT_LENGTH));
        }

        return $result;
    }

    /**
     * Key used to order operations deterministically within a timestamp.
     *
     * Ordering is by tag byte first, then by argument bytes, which matches the
     * canonical ordering of the reference implementation.
     */
    public function comparisonKey(): string
    {
        return $this->tag();
    }

    public function serialize(Serializer $serializer): void
    {
        $serializer->writeBytes($this->tag());
    }

    /**
     * Read the operation that follows the current cursor position.
     */
    final public static function deserialize(Deserializer $deserializer): self
    {
        return self::fromTag($deserializer->readBytes(1), $deserializer);
    }

    /**
     * Build the operation identified by $tag, reading any argument it needs.
     */
    final public static function fromTag(string $tag, Deserializer $deserializer): self
    {
        return match ($tag) {
            Sha1::TAG => new Sha1,
            Ripemd160::TAG => new Ripemd160,
            Sha256::TAG => new Sha256,
            Keccak256::TAG => new Keccak256,
            Reverse::TAG => new Reverse,
            Hexlify::TAG => new Hexlify,
            Append::TAG => new Append($deserializer->readVarbytes(self::MAX_RESULT_LENGTH, minLength: 1)),
            Prepend::TAG => new Prepend($deserializer->readVarbytes(self::MAX_RESULT_LENGTH, minLength: 1)),
            default => throw new SerializationException(\sprintf('Unknown operation tag 0x%02x', \ord($tag))),
        };
    }

    abstract protected function compute(string $message): string;
}
