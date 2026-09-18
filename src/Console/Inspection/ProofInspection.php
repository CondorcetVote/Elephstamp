<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Inspection;

use CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor;
use CondorcetVote\ElephStamp\{Receipt, Status};

/**
 * Everything the CLI reports about a proof, extracted once from a {@see Receipt}.
 */
final class ProofInspection
{
    /**
     * @param list<CalendarSubmission> $submissions
     * @param list<BitcoinAnchor>      $anchors
     * @param list<UnknownNotary>      $unknownNotaries
     * @param int                      $sizeInBytes     size of the serialized `.ots`
     */
    public function __construct(
        public readonly Receipt $receipt,
        public readonly array $submissions,
        public readonly array $anchors,
        public readonly array $unknownNotaries,
        public readonly int $sizeInBytes,
    ) {}

    public function status(): Status
    {
        return $this->receipt->status();
    }

    /**
     * Submissions the calendar has not confirmed yet.
     *
     * @return list<CalendarSubmission>
     */
    public function pendingSubmissions(): array
    {
        return array_values(array_filter(
            $this->submissions,
            static fn(CalendarSubmission $submission): bool => !$submission->isConfirmed(),
        ));
    }

    /**
     * The block height the proof claims, when complete.
     */
    public function bitcoinBlockHeight(): ?int
    {
        return $this->receipt->bitcoinBlockHeight();
    }
}
