<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Bitcoin;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, TimeAttestation};
use CondorcetVote\ElephStamp\Operation\{Operation, Sha256};
use CondorcetVote\ElephStamp\Timestamp;

/**
 * Finds every Bitcoin attestation of a proof and recovers its transaction.
 *
 * The path to a Bitcoin attestation ends with the transaction bytes, hashed
 * twice with SHA-256 (giving the transaction id), followed by the merkle
 * branch: for each level, the sibling hash is prepended or appended and the
 * result hashed twice again. Walking back from the attestation, the first
 * double-SHA-256 input that parses as a transaction is the transaction.
 */
final class AnchorLocator
{
    private function __construct() {}

    /**
     * @return list<BitcoinAnchor> in proof order
     */
    public static function locate(Timestamp $root): array
    {
        $anchors = [];
        self::walk($root, [], [], $anchors);

        return $anchors;
    }

    /**
     * @param list<Operation>     $ops     operations from the root to $node
     * @param list<Timestamp>     $parents nodes before each operation, so $parents[i] is where $ops[i] applies
     * @param list<BitcoinAnchor> $anchors
     */
    private static function walk(Timestamp $node, array $ops, array $parents, array &$anchors): void
    {
        $attestations = $node->attestations();
        usort($attestations, static fn(TimeAttestation $a, TimeAttestation $b): int => $a->compareTo($b));

        foreach ($attestations as $attestation) {
            if ($attestation instanceof BitcoinAttestation) {
                $anchors[] = new BitcoinAnchor($attestation, $node->msg, self::recoverTransaction($ops, $parents));
            }
        }

        $edges = $node->operations();
        usort($edges, static fn(array $a, array $b): int => strcmp($a['op']->comparisonKey(), $b['op']->comparisonKey()));

        foreach ($edges as ['op' => $op, 'timestamp' => $child]) {
            self::walk($child, [...$ops, $op], [...$parents, $node], $anchors);
        }
    }

    /**
     * @param list<Operation> $ops
     * @param list<Timestamp> $parents
     */
    private static function recoverTransaction(array $ops, array $parents): ?BitcoinTransaction
    {
        // Every double SHA-256 on the path, nearest the attestation first.
        for ($i = \count($ops) - 1; $i >= 1; --$i) {
            if (!$ops[$i] instanceof Sha256 || !$ops[$i - 1] instanceof Sha256) {
                continue;
            }

            $input = $parents[$i - 1]->msg;

            if ($input === null) {
                return null;
            }

            $transaction = BitcoinTransaction::tryFromBytes($input);

            if ($transaction !== null) {
                return $transaction;
            }
        }

        return null;
    }
}
