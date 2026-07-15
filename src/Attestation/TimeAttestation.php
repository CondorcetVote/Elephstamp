<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Attestation;

use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};

/**
 * A leaf of a timestamp proof: evidence attesting that a message existed prior
 * to some point in time.
 *
 * The two kinds that matter for this library are {@see PendingAttestation}
 * (recorded by a calendar, not yet on a blockchain) and
 * {@see BitcoinAttestation} (committed to the Bitcoin blockchain).
 */
abstract class TimeAttestation
{
    /**
     * Length of an attestation tag, in bytes.
     */
    public const int TAG_SIZE = 8;

    /**
     * Maximum size of a serialized attestation payload, in bytes.
     */
    public const int MAX_PAYLOAD_SIZE = 8192;

    /**
     * The eight-byte tag identifying this attestation type.
     */
    abstract public function tag(): string;

    /**
     * Human-readable label for the attestation, used when describing a proof.
     */
    abstract public function describe(): string;

    /**
     * Order two attestations deterministically.
     *
     * Attestations of the same type are ordered by their own criteria; those
     * of different types are ordered by tag, matching the reference
     * implementation's canonical ordering.
     */
    public function compareTo(self $other): int
    {
        return strcmp($this->tag(), $other->tag());
    }

    /**
     * Stable identity used to deduplicate attestations within a timestamp.
     */
    final public function identityKey(): string
    {
        $payload = new Serializer;
        $this->serializePayload($payload);

        return $this->tag() . $payload->getBytes();
    }

    final public function serialize(Serializer $serializer): void
    {
        $serializer->writeBytes($this->tag());

        $payload = new Serializer;
        $this->serializePayload($payload);
        $serializer->writeVarbytes($payload->getBytes());
    }

    public static function deserialize(Deserializer $deserializer): self
    {
        $tag = $deserializer->readBytes(self::TAG_SIZE);
        $payloadBytes = $deserializer->readVarbytes(self::MAX_PAYLOAD_SIZE);
        $payload = new Deserializer($payloadBytes);

        $attestation = match ($tag) {
            PendingAttestation::TAG => PendingAttestation::deserializePayload($payload),
            BitcoinAttestation::TAG => BitcoinAttestation::deserializePayload($payload),
            default => new UnknownAttestation($tag, $payloadBytes),
        };

        // Known attestations must consume their whole payload; unknown ones keep
        // the raw payload verbatim, so there is nothing left to check.
        if (!$attestation instanceof UnknownAttestation) {
            $payload->assertEof();
        }

        return $attestation;
    }

    abstract protected function serializePayload(Serializer $serializer): void;
}
