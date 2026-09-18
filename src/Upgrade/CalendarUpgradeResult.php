<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Upgrade;

/**
 * The outcome of polling one calendar for one pending commitment.
 */
final class CalendarUpgradeResult
{
    /**
     * @param string      $calendarUrl the calendar that was (or would have been) polled
     * @param string      $commitment  the raw digest the calendar was asked about
     * @param string|null $error       the calendar's error message when {@see $outcome} is {@see UpgradeOutcome::Failed} or {@see UpgradeOutcome::Rejected}
     * @param int|null    $blockHeight the lowest Bitcoin block height now attested below this submission, when there is one
     */
    public function __construct(
        public readonly string $calendarUrl,
        public readonly string $commitment,
        public readonly UpgradeOutcome $outcome,
        public readonly ?string $error = null,
        public readonly ?int $blockHeight = null,
    ) {}

    /**
     * The commitment as a lower-case hex string.
     */
    public function commitmentHex(): string
    {
        return bin2hex($this->commitment);
    }
}
