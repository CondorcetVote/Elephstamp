<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Upgrade;

use CondorcetVote\ElephStamp\Verify\{AnchorOutcome, AnchorVerification};

/**
 * The outcome of polling one calendar for one pending commitment.
 */
final class CalendarUpgradeResult
{
    /**
     * @param string      $calendarUrl the calendar that was (or would have been) polled
     * @param string      $commitment  the raw digest the calendar was asked about
     * @param string|null              $error         the calendar's error message when {@see $outcome} is {@see UpgradeOutcome::Failed} or {@see UpgradeOutcome::Rejected}, or why it could not be checked when it is {@see UpgradeOutcome::Unverifiable}
     * @param int|null                 $blockHeight   the lowest Bitcoin block height now attested below this submission, when there is one
     * @param list<AnchorVerification> $verifications how each Bitcoin attestation in the calendar's answer fared against the blockchain; empty when the answer carried none or verification was disabled
     */
    public function __construct(
        public readonly string $calendarUrl,
        public readonly string $commitment,
        public readonly UpgradeOutcome $outcome,
        public readonly ?string $error = null,
        public readonly ?int $blockHeight = null,
        public readonly array $verifications = [],
    ) {}

    /**
     * Whether every Bitcoin attestation in the calendar's answer was checked
     * against its block and found matching and deep enough; null when there
     * was nothing to check.
     */
    public function verified(): ?bool
    {
        if ($this->verifications === []) {
            return null;
        }

        foreach ($this->verifications as $verification) {
            if ($verification->outcome !== AnchorOutcome::Verified) {
                return false;
            }
        }

        return true;
    }

    /**
     * The confirmations of the shallowest block the calendar's answer names,
     * itself included. Null when nothing was checked or the block's depth is
     * unknown (its header could not be fetched).
     */
    public function confirmations(): ?int
    {
        $confirmations = null;

        foreach ($this->verifications as $verification) {
            if ($verification->confirmations === null) {
                return null;
            }

            $confirmations = min($confirmations ?? $verification->confirmations, $verification->confirmations);
        }

        return $confirmations;
    }

    /**
     * The block the calendar's answer names, verified or not: the height of
     * its shallowest Bitcoin attestation. Null when the answer named none.
     */
    public function claimedBlockHeight(): ?int
    {
        $heights = array_map(static fn(AnchorVerification $verification): int => $verification->blockHeight(), $this->verifications);

        return $heights === [] ? null : min($heights);
    }

    /**
     * The commitment as a lower-case hex string.
     */
    public function commitmentHex(): string
    {
        return bin2hex($this->commitment);
    }
}
