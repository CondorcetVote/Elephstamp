<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Calendar;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Merkle\MerkleTree;
use CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource;
use CondorcetVote\ElephStamp\{Receipt, Timestamp};
use DateTimeImmutable;

/**
 * In-memory calendar for tests and local integration environments.
 *
 * Behaves like a real calendar without any network: every submitted digest
 * starts pending, and the test drives it to completion by calling
 * {@see confirm()} or {@see confirmAll()}, simulating Bitcoin confirmation.
 *
 * Confirming mines a block into a {@see FakeBlockHeaderSource}: the
 * commitments confirmed together are bound in a merkle tree, the block's
 * merkle root is that tree's root, and each commitment gets its path to it,
 * like a real calendar aggregating its clients into one transaction. A
 * client verifying against that same source ({@see \CondorcetVote\ElephStamp\ElephStamp::fake()}
 * wires it) therefore upgrades and verifies a confirmed receipt without
 * further setup. A block is immutable once mined, so confirming more
 * commitments later takes another height: the next free one by default.
 *
 * Responses are deterministic, so a stamp produced with a
 * {@see \CondorcetVote\ElephStamp\Random\DeterministicRandomSource} is fully
 * reproducible.
 */
final class FakeCalendarClient implements CalendarClient
{
    /**
     * Block height of the first block mined, when no height is given.
     */
    public const int DEFAULT_BLOCK_HEIGHT = 800_000;

    /**
     * @var array<string, true> commitments seen via submit(), keyed by hex digest
     */
    private array $submitted = [];

    /**
     * @var array<string, Timestamp> confirmed commitments mapped to their proof, from the commitment down to the Bitcoin attestation, keyed by hex digest
     */
    private array $confirmed = [];

    private readonly FakeBlockHeaderSource $blocks;

    private ?int $lastMinedHeight = null;

    /**
     * @param int                       $defaultBlockHeight height of the first block mined when {@see confirm()} is given none; later ones follow it
     * @param FakeBlockHeaderSource|null $blocks            the chain confirmations are mined into; a fresh one by default
     */
    public function __construct(
        private readonly int $defaultBlockHeight = self::DEFAULT_BLOCK_HEIGHT,
        ?FakeBlockHeaderSource $blocks = null,
    ) {
        $this->blocks = $blocks ?? new FakeBlockHeaderSource;
    }

    /**
     * The fake chain this calendar mines its confirmations into. Verify
     * against it, or tamper with it to simulate a calendar that lies.
     */
    public function blocks(): FakeBlockHeaderSource
    {
        return $this->blocks;
    }

    public function submit(array $calendarUrls, string $digest): array
    {
        $this->submitted[bin2hex($digest)] = true;

        return array_map(
            static function (string $calendarUrl) use ($digest): CalendarResponse {
                $timestamp = new Timestamp($digest);
                $timestamp->addAttestation(new PendingAttestation($calendarUrl));

                return CalendarResponse::success($calendarUrl, $timestamp);
            },
            $calendarUrls,
        );
    }

    public function getTimestamps(array $requests): array
    {
        return array_map(
            function (array $request): CalendarResponse {
                ['url' => $url, 'commitment' => $commitment] = $request;
                $key = bin2hex($commitment);

                if (!isset($this->confirmed[$key])) {
                    return CalendarResponse::notFound($url);
                }

                return CalendarResponse::success($url, $this->confirmed[$key]);
            },
            $requests,
        );
    }

    /**
     * Confirm the commitments a specific receipt is pending on, as if Bitcoin
     * had included them in one block.
     *
     * This resolves the receipt's own pending commitments, so it works
     * regardless of whether a privacy nonce was used. Commitments already
     * confirmed are left in their block.
     *
     * @param int|null               $blockHeight the block to mine; the next free height by default
     * @param DateTimeImmutable|null $minedAt     the block's time; a fixed date by default
     *
     * @throws InvalidInputException if that height is already mined with other commitments
     */
    public function confirm(Receipt $receipt, ?int $blockHeight = null, ?DateTimeImmutable $minedAt = null): void
    {
        $commitments = array_map(
            static fn(array $entry): string => $entry['msg'],
            $receipt->detachedTimestampFile()->timestamp->findPending(),
        );

        $this->mine($commitments, $blockHeight, $minedAt);
    }

    /**
     * Confirm every digest submitted so far and not confirmed yet, in one block.
     *
     * @param int|null               $blockHeight the block to mine; the next free height by default
     * @param DateTimeImmutable|null $minedAt     the block's time; a fixed date by default
     *
     * @throws InvalidInputException if that height is already mined with other commitments
     */
    public function confirmAll(?int $blockHeight = null, ?DateTimeImmutable $minedAt = null): void
    {
        $this->mine(array_map(static fn(string $hex): string => (string) hex2bin($hex), array_keys($this->submitted)), $blockHeight, $minedAt);
    }

    /**
     * Forget all submitted and confirmed state, and the blocks mined so far.
     */
    public function reset(): void
    {
        $this->submitted = [];
        $this->confirmed = [];
        $this->lastMinedHeight = null;
        $this->blocks->reset();
    }

    /**
     * @param list<string> $commitments raw digests
     */
    private function mine(array $commitments, ?int $blockHeight, ?DateTimeImmutable $minedAt): void
    {
        $leaves = [];

        foreach (array_unique($commitments) as $commitment) {
            if (!isset($this->confirmed[bin2hex($commitment)])) {
                $leaves[bin2hex($commitment)] = new Timestamp($commitment);
            }
        }

        if ($leaves === []) {
            return;
        }

        $blockHeight ??= $this->lastMinedHeight === null ? $this->defaultBlockHeight : $this->lastMinedHeight + 1;

        $tip = MerkleTree::build(array_values($leaves));
        $tip->addAttestation(new BitcoinAttestation($blockHeight));

        if ($tip->msg === null) {
            throw new InvalidInputException('Cannot mine a merkle root that is not computable');
        }

        try {
            $this->blocks->addBlock($blockHeight, $tip->msg, $minedAt);
        } catch (InvalidInputException) {
            throw new InvalidInputException(\sprintf('Fake block %d is already mined with other commitments; confirm at another height, or at none to take the next free one', $blockHeight));
        }

        foreach ($leaves as $key => $leaf) {
            $this->confirmed[$key] = $leaf;
        }

        $this->lastMinedHeight = max($this->lastMinedHeight ?? $blockHeight, $blockHeight);
    }
}
