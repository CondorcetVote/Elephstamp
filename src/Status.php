<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp;

/**
 * Lifecycle state of a {@see Receipt}.
 */
enum Status
{
    /**
     * Recorded by a calendar, awaiting confirmation on the Bitcoin blockchain.
     */
    case Pending;

    /**
     * Confirmed by the Bitcoin blockchain; the proof is final.
     */
    case Complete;
}
