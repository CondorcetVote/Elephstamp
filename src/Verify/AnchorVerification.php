<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor;

/**
 * One Bitcoin attestation checked against the block it names.
 */
final class AnchorVerification
{
    /**
     * @param BlockHeader|null $header        the block header the source returned, when it did
     * @param int|null         $confirmations blocks mined on top of this one, itself included; null when unknown
     * @param string|null      $error         the source's error when {@see $outcome} is {@see AnchorOutcome::BlockUnavailable}
     */
    public function __construct(
        public readonly BitcoinAnchor $anchor,
        public readonly AnchorOutcome $outcome,
        public readonly ?BlockHeader $header = null,
        public readonly ?int $confirmations = null,
        public readonly ?string $error = null,
    ) {}

    public function blockHeight(): int
    {
        return $this->anchor->blockHeight();
    }
}
