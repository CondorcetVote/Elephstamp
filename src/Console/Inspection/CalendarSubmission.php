<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Inspection;

use DateTimeImmutable;

/**
 * One pending attestation of a proof, seen from the calendar's point of view.
 */
final class CalendarSubmission
{
    /**
     * @param string                 $calendarUrl           the calendar that recorded the commitment
     * @param string|null            $commitment            the raw digest the calendar holds; null below a non-computable operation
     * @param DateTimeImmutable|null $recordedAt            when the calendar recorded it, as encoded in the proof by the reference calendar server; null when the proof does not follow that layout
     * @param bool                   $upgradable            whether the calendar is on the upgrade whitelist
     * @param list<int>              $confirmedBlockHeights Bitcoin block heights already attached below this submission
     */
    public function __construct(
        public readonly string $calendarUrl,
        public readonly ?string $commitment,
        public readonly ?DateTimeImmutable $recordedAt,
        public readonly bool $upgradable,
        public readonly array $confirmedBlockHeights,
    ) {}

    public function isConfirmed(): bool
    {
        return $this->confirmedBlockHeights !== [];
    }

    public function commitmentHex(): ?string
    {
        return $this->commitment === null ? null : bin2hex($this->commitment);
    }
}
