<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Upgrade;

/**
 * What happened when a single calendar was polled during an upgrade pass.
 */
enum UpgradeOutcome
{
    /**
     * The calendar returned an attestation that was merged into the proof.
     */
    case Upgraded;

    /**
     * The calendar returned a timestamp the proof already contained.
     */
    case Unchanged;

    /**
     * The calendar has nothing yet for this commitment; poll again later.
     */
    case Pending;

    /**
     * The calendar could not be reached or answered with a protocol error.
     */
    case Failed;

    /**
     * The calendar answered with a timestamp that does not commit to the
     * requested digest: hostile or corrupt, so it was discarded.
     */
    case Rejected;

    /**
     * The calendar URI is not on the upgrade whitelist, so it was not contacted.
     */
    case Skipped;

    /**
     * A Bitcoin attestation already hangs below this submission, so there was
     * nothing left to ask the calendar. Only reported when polling an already
     * complete receipt.
     */
    case Confirmed;

    /**
     * Whether this outcome means the calendar was actually contacted.
     */
    public function wasContacted(): bool
    {
        return $this !== self::Skipped && $this !== self::Confirmed;
    }
}
