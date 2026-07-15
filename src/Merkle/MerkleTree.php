<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Merkle;

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Operation\{Append, Prepend, Sha256};
use CondorcetVote\ElephStamp\Timestamp;

/**
 * Builds the merkle tree that binds several file timestamps to a single
 * commitment, so one calendar submission covers them all.
 *
 * The algorithm is structurally a merkle mountain range and is
 * consensus-critical: it is guaranteed never to change.
 */
final class MerkleTree
{
    /**
     * Combine two timestamps: append/prepend to reach the same concatenated
     * message, then hash it. Returns the timestamp of the parent node.
     */
    public static function concatenateThenSha256(Timestamp $left, Timestamp $right): Timestamp
    {
        $rightPrepended = $right->addOp(new Prepend($left->msg));

        // The left branch reaches the same concatenated message; point its
        // append edge at the shared node so both branches converge on it.
        $left->setOp(new Append($right->msg), $rightPrepended);

        return $rightPrepended->addOp(new Sha256);
    }

    /**
     * Merkelize a list of leaf timestamps in place, returning the tip.
     *
     * @param list<Timestamp> $leaves
     *
     * @throws InvalidInputException if the list is empty
     */
    public static function build(array $leaves): Timestamp
    {
        if (empty($leaves)) {
            throw new InvalidInputException('Need at least one timestamp to build a merkle tree');
        }

        $stamps = $leaves;

        while (\count($stamps) > 1) {
            $next = [];
            $count = \count($stamps);

            for ($i = 0; $i + 1 < $count; $i += 2) {
                $next[] = self::concatenateThenSha256($stamps[$i], $stamps[$i + 1]);
            }

            // Carry a leftover odd node into the next round unchanged.
            if ($count % 2 === 1) {
                $next[] = $stamps[$count - 1];
            }

            $stamps = $next;
        }

        return $stamps[0];
    }
}
