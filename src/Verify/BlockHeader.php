<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use CondorcetVote\ElephStamp\Exception\BlockSourceException;
use DateTimeImmutable;
use DateTimeZone;

/**
 * The 80-byte header of a Bitcoin block, or the part of it verification needs.
 *
 * Hashes are kept in internal (little-endian) byte order, as in the header
 * itself and in proofs; the `*Hex()` accessors give the byte-reversed form
 * block explorers display.
 */
final class BlockHeader
{
    public const int RAW_LENGTH = 80;

    /**
     * @param string      $hash       the block hash, internal byte order
     * @param string      $merkleRoot the merkle root, internal byte order
     * @param string|null $rawHeader  the full 80-byte header when the source provided it
     */
    public function __construct(
        public readonly int $height,
        public readonly string $hash,
        public readonly string $merkleRoot,
        public readonly DateTimeImmutable $time,
        public readonly ?string $rawHeader = null,
    ) {
        if (\strlen($hash) !== 32 || \strlen($merkleRoot) !== 32) {
            throw new BlockSourceException('Block hash and merkle root must be 32 bytes');
        }

        if ($height < 0) {
            throw new BlockSourceException('Block height cannot be negative');
        }
    }

    /**
     * Parse a raw 80-byte header, computing its hash and checking that the
     * hash satisfies the difficulty the header itself declares.
     *
     * A source that hands over a raw header therefore cannot forge a merkle
     * root without also producing a valid proof of work for it.
     *
     * @throws BlockSourceException on a malformed header or a failed proof-of-work check
     */
    public static function fromRawHeader(int $height, string $rawHeader): self
    {
        if (\strlen($rawHeader) !== self::RAW_LENGTH) {
            throw new BlockSourceException(\sprintf('A block header is %d bytes, got %d', self::RAW_LENGTH, \strlen($rawHeader)));
        }

        $fields = unpack('Vtime/Vbits', $rawHeader, 68);

        if ($fields === false) {
            throw new BlockSourceException('Unable to decode the block header');
        }

        $hash = hash('sha256', hash('sha256', $rawHeader, binary: true), binary: true);

        if (!self::meetsTarget($hash, (int) $fields['bits'])) {
            throw new BlockSourceException('Block header does not satisfy its own proof of work');
        }

        return new self(
            $height,
            $hash,
            substr($rawHeader, 36, 32),
            new DateTimeImmutable('@' . (int) $fields['time'])->setTimezone(new DateTimeZone('UTC')),
            $rawHeader,
        );
    }

    /**
     * The block hash as explorers display it (byte-reversed hex).
     */
    public function hashHex(): string
    {
        return bin2hex(strrev($this->hash));
    }

    /**
     * The merkle root as explorers display it (byte-reversed hex).
     */
    public function merkleRootHex(): string
    {
        return bin2hex(strrev($this->merkleRoot));
    }

    /**
     * Whether a hash (internal byte order) is at or below the target encoded
     * in a header's compact "bits" field.
     */
    private static function meetsTarget(string $hash, int $bits): bool
    {
        $exponent = ($bits >> 24) & 0xFF;
        $mantissa = $bits & 0x007FFFFF;

        // A negative target (sign bit set) or one that overflows 256 bits is
        // invalid in Bitcoin; no honest header carries such bits.
        if (($bits & 0x00800000) !== 0 || $mantissa === 0 || $exponent > 32) {
            return false;
        }

        $mantissaBytes = substr(pack('N', $mantissa), 1);
        $shift = $exponent - 3;

        if ($shift < 0) {
            $mantissaBytes = substr($mantissaBytes, 0, 3 + $shift);
            $shift = 0;
        }

        // Target as a 32-byte big-endian integer: mantissa shifted left by
        // $shift bytes.
        $target = str_repeat("\0", 32 - \strlen($mantissaBytes) - $shift) . $mantissaBytes . str_repeat("\0", $shift);

        return strcmp(strrev($hash), $target) <= 0;
    }
}
