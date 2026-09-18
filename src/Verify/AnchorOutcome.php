<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

/**
 * What checking one Bitcoin attestation against a block header gave.
 */
enum AnchorOutcome
{
    /**
     * The block's merkle root equals the one the proof recomputes, and the
     * block is buried under enough confirmations.
     */
    case Verified;

    /**
     * The merkle root matches, but the block is too recent: fewer
     * confirmations than required. Verify again later.
     */
    case AwaitingConfirmations;

    /**
     * The block's merkle root differs from the proof's: the proof is corrupt,
     * forged, or names the wrong block.
     */
    case MerkleRootMismatch;

    /**
     * The source could not provide the header (unreachable, unknown block,
     * inconsistent answer). Nothing can be concluded.
     */
    case BlockUnavailable;

    /**
     * The proof's merkle root is unknown because the attestation sits below
     * an operation this library cannot compute.
     */
    case NotComputable;

    /**
     * Whether the merkle root was found to match, regardless of depth.
     */
    public function matches(): bool
    {
        return $this === self::Verified || $this === self::AwaitingConfirmations;
    }
}
