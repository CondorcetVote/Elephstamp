<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Inspection;

/**
 * An attestation of a type this library does not understand.
 */
final class UnknownNotary
{
    public function __construct(
        public readonly string $tag,
        public readonly int $payloadLength,
    ) {}

    public function tagHex(): string
    {
        return bin2hex($this->tag);
    }
}
