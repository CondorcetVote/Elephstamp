<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Upgrade;

/**
 * Detailed account of one {@see \CondorcetVote\ElephStamp\ElephStamp::upgradeWithReport()} pass.
 *
 * Holds one {@see CalendarUpgradeResult} per pending attestation found in the
 * receipt, including the ones that were not contacted because their calendar
 * is not whitelisted. A receipt that was already complete yields an empty
 * report.
 */
final class UpgradeReport
{
    /**
     * @param list<CalendarUpgradeResult> $results
     */
    public function __construct(public readonly array $results) {}

    /**
     * Whether the pass added anything new to the receipt.
     */
    public function changed(): bool
    {
        return $this->count(UpgradeOutcome::Upgraded) > 0;
    }

    /**
     * Number of results with the given outcome.
     */
    public function count(UpgradeOutcome $outcome): int
    {
        return \count($this->filter($outcome));
    }

    /**
     * Results with the given outcome, in polling order.
     *
     * @return list<CalendarUpgradeResult>
     */
    public function filter(UpgradeOutcome $outcome): array
    {
        return array_values(array_filter(
            $this->results,
            static fn(CalendarUpgradeResult $result): bool => $result->outcome === $outcome,
        ));
    }
}
