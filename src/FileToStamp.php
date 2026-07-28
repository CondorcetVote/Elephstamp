<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp;

use Closure;
use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Operation\HashOperation;
use SplFileObject;

/**
 * A piece of data to be timestamped, together with its privacy preference.
 *
 * Built through an explicit named constructor for each supported source. The
 * digest is always computed in a streaming fashion; file content is never held
 * in memory in full (except when you deliberately pass it as a string).
 *
 * By default a random nonce is mixed in so the calendar never learns the real
 * digest. Call {@see withoutNonce()} when linkability is acceptable or desired
 * (the commitment then equals the file's plain hash).
 */
final class FileToStamp
{
    /**
     * Size of the buffer used when reading files, in bytes.
     */
    private const CHUNK_SIZE = 1_048_576;

    /**
     * @param Closure(HashOperation): string $digestFactory computes the file digest with a given hash operation
     */
    private function __construct(
        private readonly Closure $digestFactory,
        public readonly bool $useNonce,
    ) {}

    /**
     * Timestamp a raw string already held in memory.
     */
    public static function fromContent(string $content): self
    {
        return new self(static fn(HashOperation $operation): string => $operation->hashData($content), useNonce: true);
    }

    /**
     * Timestamp a digest that has already been computed elsewhere.
     *
     * The digest is used verbatim (never re-hashed); its length must match the
     * hash operation the client is configured with (SHA-256 by default).
     *
     * @throws InvalidInputException if the digest is empty
     */
    public static function fromDigest(string $digest): self
    {
        if ($digest === '') {
            throw new InvalidInputException('Digest cannot be empty');
        }

        return new self(
            static function (HashOperation $operation) use ($digest): string {
                if (\strlen($digest) !== $operation->digestLength()) {
                    throw new InvalidInputException(\sprintf(
                        'Digest length %d does not match the %s digest length of %d',
                        \strlen($digest),
                        $operation::class,
                        $operation->digestLength(),
                    ));
                }

                return $digest;
            },
            useNonce: true,
        );
    }

    /**
     * Timestamp the file at the given path, read as a stream.
     *
     * @throws InvalidInputException if the path is not a readable file
     */
    public static function fromPath(string $path): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidInputException(\sprintf('File does not exist or is not readable: %s', $path));
        }

        return new self(
            static function (HashOperation $operation) use ($path): string {
                $stream = fopen($path, 'rb');

                if ($stream === false) {
                    throw new InvalidInputException(\sprintf('Unable to open file for reading: %s', $path));
                }

                try {
                    return $operation->hashStream($stream);
                } finally {
                    fclose($stream);
                }
            },
            useNonce: true,
        );
    }

    /**
     * Timestamp an already-open, readable file handle, read as a stream.
     *
     * @throws InvalidInputException if the handle is not readable
     */
    public static function fromSplFileObject(SplFileObject $file): self
    {
        if (!$file->isReadable()) {
            throw new InvalidInputException('The provided SplFileObject is not readable');
        }

        return new self(
            static fn(HashOperation $operation): string => $operation->hashChunks(self::readChunks($file)),
            useNonce: true,
        );
    }

    /**
     * Return a copy that commits to the plain file digest, without a nonce.
     *
     * Privacy caveats: the calendars learn the plain digest, and inside a
     * {@see ElephStamp::stampMany()} batch the digest is also embedded in the
     * sibling receipts, so whoever holds one of them learns it too.
     */
    public function withoutNonce(): self
    {
        return new self($this->digestFactory, useNonce: false);
    }

    /**
     * Return a copy that mixes in a random nonce (the default).
     */
    public function withNonce(): self
    {
        return new self($this->digestFactory, useNonce: true);
    }

    /**
     * Compute this file's digest with the given hash operation.
     */
    public function digest(HashOperation $operation): string
    {
        return ($this->digestFactory)($operation);
    }

    /**
     * @return iterable<string>
     */
    private static function readChunks(SplFileObject $file): iterable
    {
        $file->rewind();

        while (!$file->eof()) {
            $chunk = $file->fread(self::CHUNK_SIZE);

            if ($chunk === false) {
                throw new InvalidInputException('Failed while reading the SplFileObject');
            }

            yield $chunk;
        }
    }
}
