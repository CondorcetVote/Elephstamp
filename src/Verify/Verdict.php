<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

/**
 * The overall conclusion of verifying a receipt.
 */
enum Verdict
{
    /**
     * The file matches the proof (when given) and at least one Bitcoin
     * attestation is confirmed by a block with the expected merkle root.
     */
    case Verified;

    /**
     * Same as {@see Verified}, except every matching block is still too
     * recent to reach the required confirmations.
     */
    case AwaitingConfirmations;

    /**
     * The file does not match the proof, or a block's merkle root differs
     * from the proof's. The proof is not valid for this file.
     */
    case Failed;

    /**
     * The proof carries no Bitcoin attestation yet; upgrade it first.
     */
    case Pending;

    /**
     * There are Bitcoin attestations, but none could be checked: the source
     * was unavailable, or the attestations are not computable.
     */
    case Inconclusive;
}
