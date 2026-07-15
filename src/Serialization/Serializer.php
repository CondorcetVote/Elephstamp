<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Serialization;

/**
 * Writer for the OpenTimestamps binary format.
 *
 * Accumulates bytes into an in-memory buffer. Timestamp proofs are small
 * (kilobytes), so buffering the whole output is appropriate here; only the
 * hashing of the timestamped payload itself is done in a streaming fashion.
 */
final class Serializer
{
    private string $buffer = '';

    public function writeBool(bool $value): void
    {
        $this->buffer .= $value ? "\xff" : "\x00";
    }

    public function writeUint8(int $value): void
    {
        if ($value < 0 || $value > 0xFF) {
            throw new \CondorcetVote\ElephStamp\Exception\SerializationException(\sprintf('uint8 out of range: %d', $value));
        }

        $this->buffer .= \chr($value);
    }

    /**
     * Write a variable-length unsigned integer (unsigned little-endian base-128, LEB128).
     */
    public function writeVaruint(int $value): void
    {
        if ($value < 0) {
            throw new \CondorcetVote\ElephStamp\Exception\SerializationException(\sprintf('varuint cannot be negative: %d', $value));
        }

        if ($value === 0) {
            $this->buffer .= "\x00";

            return;
        }

        while (true) {
            $byte = $value & 0b0111_1111;

            if ($value > 0b0111_1111) {
                $byte |= 0b1000_0000;
            }

            $this->buffer .= \chr($byte);

            if ($value <= 0b0111_1111) {
                break;
            }

            $value >>= 7;
        }
    }

    /**
     * Write fixed-length bytes verbatim (no length prefix).
     */
    public function writeBytes(string $value): void
    {
        $this->buffer .= $value;
    }

    /**
     * Write variable-length bytes: a varuint length prefix followed by the bytes.
     */
    public function writeVarbytes(string $value): void
    {
        $this->writeVaruint(\strlen($value));
        $this->buffer .= $value;
    }

    /**
     * Return everything written so far.
     */
    public function getBytes(): string
    {
        return $this->buffer;
    }
}
