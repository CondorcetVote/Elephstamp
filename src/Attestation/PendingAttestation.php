<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Attestation;

use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};

/**
 * A commitment recorded by a remote calendar, awaiting confirmation.
 *
 * The URI points at the calendar that promised to include the commitment in a
 * future blockchain attestation; it is where an upgrade request is sent to
 * fetch the completed proof.
 */
final class PendingAttestation extends TimeAttestation
{
    public const TAG = "\x83\xdf\xe3\x0d\x2e\xf9\x0c\x8e";

    public const MAX_URI_LENGTH = 1000;

    /**
     * Characters permitted in a calendar URI.
     *
     * Deliberately excludes the characters needed for query strings, fragments,
     * userinfo and IPv6 literals, keeping URIs simple and unambiguous.
     */
    public const ALLOWED_URI_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._/:';

    public function __construct(public readonly string $uri)
    {
        self::checkUri($uri);
    }

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'pending attestation → ' . $this->uri;
    }

    public function compareTo(TimeAttestation $other): int
    {
        if ($other instanceof self) {
            return strcmp($this->uri, $other->uri);
        }

        return parent::compareTo($other);
    }

    public static function deserializePayload(Deserializer $deserializer): self
    {
        return new self($deserializer->readVarbytes(self::MAX_URI_LENGTH));
    }

    protected function serializePayload(Serializer $serializer): void
    {
        $serializer->writeVarbytes($this->uri);
    }

    private static function checkUri(string $uri): void
    {
        if (\strlen($uri) > self::MAX_URI_LENGTH) {
            throw new SerializationException(\sprintf('Calendar URI exceeds maximum length of %d bytes', self::MAX_URI_LENGTH));
        }

        if (strspn($uri, self::ALLOWED_URI_CHARS) !== \strlen($uri)) {
            throw new SerializationException('Calendar URI contains an invalid character');
        }
    }
}
