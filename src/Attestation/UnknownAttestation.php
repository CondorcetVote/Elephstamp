<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Attestation;

use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Serialization\Serializer;

/**
 * An attestation whose type this library does not understand (for example
 * Litecoin or Ethereum).
 *
 * Its raw payload is preserved verbatim so proofs round-trip losslessly, but
 * no meaning is attached to it.
 */
final class UnknownAttestation extends TimeAttestation
{
    /**
     * @param string $tag     the eight-byte type tag
     * @param string $payload the raw, unparsed payload
     */
    public function __construct(
        private readonly string $tag,
        public readonly string $payload,
    ) {
        if (\strlen($tag) !== self::TAG_SIZE) {
            throw new SerializationException(\sprintf('Attestation tag must be exactly %d bytes', self::TAG_SIZE));
        }

        if (\strlen($payload) > self::MAX_PAYLOAD_SIZE) {
            throw new SerializationException(\sprintf('Attestation payload exceeds maximum size of %d bytes', self::MAX_PAYLOAD_SIZE));
        }
    }

    public function tag(): string
    {
        return $this->tag;
    }

    public function describe(): string
    {
        return 'unknown attestation (tag ' . bin2hex($this->tag) . ')';
    }

    public function compareTo(TimeAttestation $other): int
    {
        if ($other instanceof self) {
            return [$this->tag, $this->payload] <=> [$other->tag, $other->payload];
        }

        return parent::compareTo($other);
    }

    protected function serializePayload(Serializer $serializer): void
    {
        // Raw bytes, no length header: the outer serialize() already wraps the
        // payload in a varbytes length prefix.
        $serializer->writeBytes($this->payload);
    }
}
