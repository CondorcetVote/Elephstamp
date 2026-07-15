<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Serialization;

use CondorcetVote\ElephStamp\Exception\SerializationException;

/**
 * Reader for the OpenTimestamps binary format.
 *
 * Consumes a fixed byte string with a moving cursor. All read failures raise
 * {@see SerializationException}.
 */
final class Deserializer
{
    private int $offset = 0;

    private readonly int $length;

    public function __construct(private readonly string $data)
    {
        $this->length = \strlen($data);
    }

    /**
     * Read exactly $count raw bytes, advancing the cursor.
     */
    public function readBytes(int $count): string
    {
        if ($count < 0) {
            throw new SerializationException(\sprintf('Cannot read a negative number of bytes: %d', $count));
        }

        if ($this->offset + $count > $this->length) {
            throw new SerializationException(\sprintf('Tried to read %d bytes but only %d remain', $count, $this->length - $this->offset));
        }

        $chunk = substr($this->data, $this->offset, $count);
        $this->offset += $count;

        return $chunk;
    }

    public function readUint8(): int
    {
        return \ord($this->readBytes(1));
    }

    public function readBool(): bool
    {
        $byte = $this->readBytes(1);

        return match ($byte) {
            "\xff" => true,
            "\x00" => false,
            default => throw new SerializationException(\sprintf('Expected boolean 0xff or 0x00; got 0x%02x', \ord($byte))),
        };
    }

    /**
     * Read a variable-length unsigned integer (LEB128).
     */
    public function readVaruint(): int
    {
        $value = 0;
        $shift = 0;

        while (true) {
            $byte = \ord($this->readBytes(1));
            $value |= ($byte & 0b0111_1111) << $shift;

            if (($byte & 0b1000_0000) === 0) {
                break;
            }

            $shift += 7;

            if ($shift >= 63) {
                throw new SerializationException('varuint is too large to be represented as a 64-bit integer');
            }
        }

        return $value;
    }

    /**
     * Read variable-length bytes: a varuint length prefix, then that many bytes.
     */
    public function readVarbytes(int $maxLength, int $minLength = 0): string
    {
        $length = $this->readVaruint();

        if ($length > $maxLength) {
            throw new SerializationException(\sprintf('varbytes exceeds maximum length: %d > %d', $length, $maxLength));
        }

        if ($length < $minLength) {
            throw new SerializationException(\sprintf('varbytes shorter than minimum length: %d < %d', $length, $minLength));
        }

        return $this->readBytes($length);
    }

    /**
     * Assert that the upcoming bytes match the expected magic header.
     */
    public function assertMagic(string $expectedMagic): void
    {
        $actualMagic = $this->readBytes(\strlen($expectedMagic));

        if ($actualMagic !== $expectedMagic) {
            throw new SerializationException(\sprintf(
                'Expected magic bytes 0x%s, but got 0x%s',
                bin2hex($expectedMagic),
                bin2hex($actualMagic),
            ));
        }
    }

    /**
     * Assert that the cursor has reached the end of the data.
     */
    public function assertEof(): void
    {
        if ($this->offset !== $this->length) {
            throw new SerializationException('Trailing garbage found after end of deserialized data');
        }
    }

    public function isEof(): bool
    {
        return $this->offset >= $this->length;
    }
}
