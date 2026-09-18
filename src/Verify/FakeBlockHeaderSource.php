<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use CondorcetVote\ElephStamp\Exception\{BlockSourceException, InvalidInputException};
use CondorcetVote\ElephStamp\Receipt;
use DateTimeImmutable;
use DateTimeZone;

/**
 * In-memory block headers for tests and local environments.
 *
 * Register the blocks a proof claims, then verify it without any network.
 * {@see anchor()} does the common thing: it registers, for every Bitcoin
 * attestation of a receipt, a block whose merkle root is the one the receipt
 * recomputes, so the receipt verifies.
 */
final class FakeBlockHeaderSource implements BlockHeaderSource
{
    /**
     * @var array<int, BlockHeader>
     */
    private array $headers = [];

    private ?int $tipHeight = null;

    /**
     * Register a block. Its hash is derived from the height, since nothing
     * here is real.
     *
     * @param string $merkleRoot 32 bytes, internal byte order
     *
     * @throws InvalidInputException if the height already holds a different merkle root
     */
    public function addBlock(int $height, string $merkleRoot, ?DateTimeImmutable $time = null): void
    {
        if (isset($this->headers[$height]) && !hash_equals($this->headers[$height]->merkleRoot, $merkleRoot)) {
            throw new InvalidInputException(\sprintf('Fake block %d already holds a different merkle root; use distinct heights', $height));
        }

        $this->headers[$height] = new BlockHeader(
            $height,
            hash('sha256', 'fake block ' . $height, binary: true),
            $merkleRoot,
            ($time ?? new DateTimeImmutable('2024-01-01 00:00:00'))->setTimezone(new DateTimeZone('UTC')),
        );
    }

    /**
     * Register a block for every Bitcoin attestation of the receipt, so that
     * verifying it succeeds.
     */
    public function anchor(Receipt $receipt, ?DateTimeImmutable $time = null): void
    {
        foreach ($receipt->bitcoinAnchors() as $anchor) {
            if ($anchor->merkleRoot !== null) {
                $this->addBlock($anchor->blockHeight(), $anchor->merkleRoot, $time);
            }
        }
    }

    /**
     * Pretend the chain has grown to this height. Defaults to the highest
     * registered block plus enough for {@see Verifier::DEFAULT_REQUIRED_CONFIRMATIONS}.
     */
    public function setTipHeight(int $tipHeight): void
    {
        $this->tipHeight = $tipHeight;
    }

    public function blockHeader(int $height): BlockHeader
    {
        return $this->headers[$height] ?? throw new BlockSourceException(\sprintf('fake source does not know block %d', $height));
    }

    public function tipHeight(): int
    {
        if ($this->tipHeight !== null) {
            return $this->tipHeight;
        }

        if ($this->headers === []) {
            throw new BlockSourceException('fake source has no block registered');
        }

        return max(array_keys($this->headers)) + Verifier::DEFAULT_REQUIRED_CONFIRMATIONS - 1;
    }

    public function describe(): string
    {
        return 'fake block source';
    }

    /**
     * Forget every registered block.
     */
    public function reset(): void
    {
        $this->headers = [];
        $this->tipHeight = null;
    }
}
