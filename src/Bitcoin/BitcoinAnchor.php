<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Bitcoin;

use CondorcetVote\ElephStamp\Attestation\BitcoinAttestation;

/**
 * One Bitcoin attestation of a proof together with what the proof path
 * reveals about it: the block's merkle root it commits to and, when the path
 * follows the usual layout, the transaction that carries the commitment.
 *
 * Nothing here is verified against the blockchain; these are the values a
 * verifier would compare with the block header and the transaction.
 */
final class BitcoinAnchor
{
    /**
     * @param string|null             $merkleRoot  the block merkle root the attestation commits to, in header byte order; null below a non-computable operation
     * @param BitcoinTransaction|null $transaction the transaction carrying the commitment; null when the path does not embed a recognisable transaction followed by a merkle branch
     */
    public function __construct(
        public readonly BitcoinAttestation $attestation,
        public readonly ?string $merkleRoot,
        public readonly ?BitcoinTransaction $transaction,
    ) {}

    public function blockHeight(): int
    {
        return $this->attestation->blockHeight;
    }

    /**
     * The merkle root as block explorers display it (byte-reversed hex).
     */
    public function merkleRootHex(): ?string
    {
        return $this->merkleRoot === null ? null : bin2hex(strrev($this->merkleRoot));
    }
}
