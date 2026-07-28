<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp;

use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Operation\HashOperation;
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};

/**
 * A timestamp bound to the digest of a specific file — the content of an
 * `.ots` proof file.
 */
final class DetachedTimestampFile
{
    /**
     * Header magic. Designed to give a hint in a hexdump while being detected
     * as "data" by the `file` utility.
     */
    public const HEADER_MAGIC = "\x00OpenTimestamps\x00\x00Proof\x00\xbf\x89\xe2\xe8\x84\xe8\x92\x94";

    public const MAJOR_VERSION = 1;

    private readonly string $fileDigest;

    public function __construct(
        public readonly HashOperation $fileHashOperation,
        public readonly Timestamp $timestamp,
    ) {
        if ($timestamp->msg === null || \strlen($timestamp->msg) !== $fileHashOperation->digestLength()) {
            throw new SerializationException('Timestamp message length does not match the file-hash digest length');
        }

        $this->fileDigest = $timestamp->msg;
    }

    /**
     * The digest of the timestamped file.
     */
    public function fileDigest(): string
    {
        return $this->fileDigest;
    }

    public function serialize(Serializer $serializer): void
    {
        $serializer->writeBytes(self::HEADER_MAGIC);
        $serializer->writeUint8(self::MAJOR_VERSION);
        $this->fileHashOperation->serialize($serializer);
        $serializer->writeBytes($this->fileDigest);
        $this->timestamp->serialize($serializer);
    }

    /**
     * Serialize to the raw bytes of an `.ots` file.
     */
    public function toBytes(): string
    {
        $serializer = new Serializer;
        $this->serialize($serializer);

        return $serializer->getBytes();
    }

    /**
     * Parse the raw bytes of an `.ots` file.
     *
     * @throws SerializationException on malformed input
     */
    public static function fromBytes(string $bytes): self
    {
        $deserializer = new Deserializer($bytes);
        $deserializer->assertMagic(self::HEADER_MAGIC);

        $major = $deserializer->readUint8();

        if ($major !== self::MAJOR_VERSION) {
            throw new SerializationException(\sprintf('Unsupported detached timestamp major version: %d', $major));
        }

        $fileHashOperation = HashOperation::deserializeHash($deserializer);
        $fileDigest = $deserializer->readBytes($fileHashOperation->digestLength());
        $timestamp = Timestamp::deserialize($deserializer, $fileDigest);

        $deserializer->assertEof();

        return new self($fileHashOperation, $timestamp);
    }
}
