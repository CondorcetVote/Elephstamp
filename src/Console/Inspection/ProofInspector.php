<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Inspection;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation, TimeAttestation, UnknownAttestation};
use CondorcetVote\ElephStamp\Calendar\CalendarWhitelist;
use CondorcetVote\ElephStamp\Operation\{Append, Operation, Prepend};
use CondorcetVote\ElephStamp\{Receipt, Timestamp};
use DateTimeImmutable;
use DateTimeZone;

/**
 * Walks a proof tree and gathers what each calendar submission is up to.
 */
final class ProofInspector
{
    /**
     * The reference calendar server records the submission time as a 4-byte
     * big-endian Unix timestamp prepended right before an 8-byte per-second
     * key is appended; the pending attestation hangs off that last node.
     */
    private const int RECORDED_AT_LENGTH = 4;

    private const int PER_SECOND_KEY_LENGTH = 8;

    /**
     * Earliest plausible calendar time (OpenTimestamps did not exist before 2016).
     */
    private const int EARLIEST_PLAUSIBLE_TIME = 1_451_606_400;

    public function __construct(private readonly CalendarWhitelist $whitelist) {}

    public function inspect(Receipt $receipt): ProofInspection
    {
        $submissions = [];
        $unknown = [];

        $root = $receipt->detachedTimestampFile()->timestamp;
        $this->walk($root, [], $submissions, $unknown);

        return new ProofInspection(
            $receipt,
            $submissions,
            $receipt->bitcoinAnchors(),
            $unknown,
            \strlen($receipt->toBytes()),
            SubmissionPoint::locate($root),
        );
    }

    /**
     * @param list<Operation>          $path        operations leading from the file digest to $node
     * @param list<CalendarSubmission> $submissions
     * @param list<UnknownNotary>      $unknown
     */
    private function walk(Timestamp $node, array $path, array &$submissions, array &$unknown): void
    {
        $attestations = $node->attestations();
        usort($attestations, static fn(TimeAttestation $a, TimeAttestation $b): int => $a->compareTo($b));

        foreach ($attestations as $attestation) {
            if ($attestation instanceof PendingAttestation) {
                $submissions[] = new CalendarSubmission(
                    $attestation->uri,
                    $node->msg,
                    self::recordedAt($path),
                    $this->whitelist->allows($attestation->uri),
                    self::blockHeightsBelow($node),
                );
            } elseif ($attestation instanceof UnknownAttestation) {
                $unknown[] = new UnknownNotary($attestation->tag(), \strlen($attestation->payload));
            }
        }

        $ops = $node->operations();
        usort($ops, static fn(array $a, array $b): int => strcmp($a['op']->comparisonKey(), $b['op']->comparisonKey()));

        foreach ($ops as ['op' => $op, 'timestamp' => $child]) {
            $this->walk($child, [...$path, $op], $submissions, $unknown);
        }
    }

    /**
     * @param list<Operation> $path
     */
    private static function recordedAt(array $path): ?DateTimeImmutable
    {
        $count = \count($path);

        if ($count < 2) {
            return null;
        }

        $key = $path[$count - 1];
        $time = $path[$count - 2];

        if (!$key instanceof Append || \strlen($key->argument) !== self::PER_SECOND_KEY_LENGTH) {
            return null;
        }

        if (!$time instanceof Prepend || \strlen($time->argument) !== self::RECORDED_AT_LENGTH) {
            return null;
        }

        $unpacked = unpack('Nseconds', $time->argument);

        if ($unpacked === false) {
            return null;
        }

        $seconds = $unpacked['seconds'];

        // The layout match could be a coincidence: only trust a value that
        // falls in a plausible window.
        if ($seconds < self::EARLIEST_PLAUSIBLE_TIME || $seconds > time() + 86_400) {
            return null;
        }

        return new DateTimeImmutable('@' . $seconds)->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * @return list<int>
     */
    private static function blockHeightsBelow(Timestamp $node): array
    {
        $heights = [];

        foreach ($node->allAttestations() as ['attestation' => $attestation]) {
            if ($attestation instanceof BitcoinAttestation) {
                $heights[] = $attestation->blockHeight;
            }
        }

        sort($heights);

        return array_values(array_unique($heights));
    }
}
