<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use CondorcetVote\ElephStamp\Exception\BlockSourceException;

/**
 * Where block headers come from when verifying a proof.
 *
 * Verifying a complete proof needs exactly one piece of external data per
 * Bitcoin attestation: the header of the block it names, whose merkle root
 * must equal the one the proof recomputes. This interface is deliberately
 * neutral about who provides it: a public block explorer today, a Bitcoin
 * node tomorrow, an in-memory fake in tests.
 */
interface BlockHeaderSource
{
    /**
     * The header of the block at the given height.
     *
     * @throws BlockSourceException when the source is unreachable, knows no such block, or answers inconsistently
     */
    public function blockHeader(int $height): BlockHeader;

    /**
     * The height of the best block the source knows about, used to count
     * confirmations.
     *
     * @throws BlockSourceException when the source is unreachable
     */
    public function tipHeight(): int;

    /**
     * Human-readable name of the source, e.g. "mempool.space".
     */
    public function describe(): string;
}
