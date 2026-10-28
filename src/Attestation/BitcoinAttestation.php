<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Attestation;

use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};

/**
 * A commitment confirmed by the Bitcoin blockchain.
 *
 * The presence of this attestation means the timestamp is complete: the
 * committed digest is the merkle root of the block at the recorded height.
 *
 * This library does not verify the attestation against the blockchain; it only
 * reports the height so callers can look it up with the tool of their choice.
 */
final class BitcoinAttestation extends TimeAttestation
{
    public const string TAG = "\x05\x88\x96\x0d\x73\xd7\x19\x01";

    /**
     * Highest block height a proof may name: 32 bits, as the reference
     * implementation bounds it. Bitcoin will not get there for millennia, so
     * anything above is malformed input rather than a future-proofing gap.
     */
    public const int MAX_HEIGHT = 0xFFFF_FFFF;

    public function __construct(public readonly int $blockHeight)
    {
        if ($blockHeight < 0) {
            throw new SerializationException(\sprintf('Bitcoin block height cannot be negative: %d', $blockHeight));
        }

        if ($blockHeight > self::MAX_HEIGHT) {
            throw new SerializationException(\sprintf('Bitcoin block height too large: %d > %d', $blockHeight, self::MAX_HEIGHT));
        }
    }

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'bitcoin attestation → block ' . $this->blockHeight;
    }

    public function compareTo(TimeAttestation $other): int
    {
        if ($other instanceof self) {
            return $this->blockHeight <=> $other->blockHeight;
        }

        return parent::compareTo($other);
    }

    public static function deserializePayload(Deserializer $deserializer): self
    {
        return new self($deserializer->readVaruint());
    }

    protected function serializePayload(Serializer $serializer): void
    {
        $serializer->writeVaruint($this->blockHeight);
    }
}
