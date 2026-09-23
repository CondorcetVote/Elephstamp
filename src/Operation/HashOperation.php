<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

use CondorcetVote\ElephStamp\Exception\{InvalidInputException, SerializationException};
use CondorcetVote\ElephStamp\Serialization\Deserializer;

/**
 * A cryptographic hash operation.
 *
 * Unlike other operations these produce a fixed-length result regardless of
 * input size, which is what allows a whole file to be hashed as a stream.
 * They are also the only operations valid as the file-hash operation of a
 * detached timestamp.
 */
abstract class HashOperation extends UnaryOperation
{
    /**
     * The hash operations a detached timestamp can be built with, by name as
     * {@see describe()} returns it; the default one first.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return ['sha256', 'sha1', 'ripemd160', 'keccak256'];
    }

    /**
     * The hash operation with the given name, as {@see describe()} returns it.
     *
     * @throws InvalidInputException on an unknown name
     */
    public static function fromName(string $name): self
    {
        return match (strtolower($name)) {
            'sha256' => new Sha256,
            'sha1' => new Sha1,
            'ripemd160' => new Ripemd160,
            'keccak256' => new Keccak256,
            default => throw new InvalidInputException(\sprintf('Unknown hash operation "%s"; known: %s', $name, implode(', ', self::names()))),
        };
    }

    /**
     * Length of the digest this operation produces, in bytes.
     */
    abstract public function digestLength(): int;

    /**
     * Hash a whole in-memory payload.
     *
     * Unlike {@see Operation::apply()} this is not bound by the proof message
     * length limits: it hashes the original file content, which can be large.
     */
    abstract public function hashData(string $data): string;

    /**
     * Hash a stream from its current position to its end.
     *
     * @param resource $stream
     */
    abstract public function hashStream($stream): string;

    /**
     * Hash a sequence of chunks as one message.
     *
     * @param iterable<string> $chunks
     */
    abstract public function hashChunks(iterable $chunks): string;

    protected function compute(string $message): string
    {
        return $this->hashData($message);
    }

    /**
     * Read a cryptographic hash operation; reject non-hash operations.
     *
     * Used for the file-hash operation field of a detached timestamp, which
     * must be a cryptographic hash.
     */
    final public static function deserializeHash(Deserializer $deserializer): self
    {
        $operation = Operation::deserialize($deserializer);

        if (!$operation instanceof self) {
            throw new SerializationException(\sprintf('Expected a cryptographic hash operation, got %s', $operation::class));
        }

        return $operation;
    }
}
