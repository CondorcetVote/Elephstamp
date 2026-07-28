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
    public const TAG = "\x05\x88\x96\x0d\x73\xd7\x19\x01";

    public function __construct(public readonly int $blockHeight)
    {
        if ($blockHeight < 0) {
            throw new SerializationException(\sprintf('Bitcoin block height cannot be negative: %d', $blockHeight));
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
