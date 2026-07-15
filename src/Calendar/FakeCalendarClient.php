<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Calendar;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\{Receipt, Timestamp};

/**
 * In-memory calendar for tests and local integration environments.
 *
 * Behaves like a real calendar without any network: every submitted digest
 * starts pending, and the test drives it to completion by calling
 * {@see confirm()} or {@see confirmAll()}, simulating Bitcoin confirmation.
 *
 * Responses are deterministic, so a stamp produced with a
 * {@see \CondorcetVote\ElephStamp\Random\DeterministicRandomSource} is fully
 * reproducible.
 */
final class FakeCalendarClient implements CalendarClient
{
    /**
     * Default block height reported once a commitment is confirmed.
     */
    public const int DEFAULT_BLOCK_HEIGHT = 800_000;

    /**
     * @var array<string, true> commitments seen via submit(), keyed by hex digest
     */
    private array $submitted = [];

    /**
     * @var array<string, int> confirmed commitments mapped to their block height, keyed by hex digest
     */
    private array $confirmed = [];

    public function __construct(private readonly int $defaultBlockHeight = self::DEFAULT_BLOCK_HEIGHT) {}

    public function submit(array $calendarUrls, string $digest): array
    {
        $this->submitted[bin2hex($digest)] = true;

        return array_map(
            static function (string $calendarUrl) use ($digest): CalendarResponse {
                $timestamp = new Timestamp($digest);
                $timestamp->addAttestation(new PendingAttestation($calendarUrl));

                return CalendarResponse::success($calendarUrl, $timestamp);
            },
            $calendarUrls,
        );
    }

    public function getTimestamps(array $requests): array
    {
        return array_map(
            function (array $request): CalendarResponse {
                ['url' => $url, 'commitment' => $commitment] = $request;
                $key = bin2hex($commitment);

                if (!isset($this->confirmed[$key])) {
                    return CalendarResponse::notFound($url);
                }

                $timestamp = new Timestamp($commitment);
                $timestamp->addAttestation(new BitcoinAttestation($this->confirmed[$key]));

                return CalendarResponse::success($url, $timestamp);
            },
            $requests,
        );
    }

    /**
     * Confirm the commitments a specific receipt is pending on, as if Bitcoin
     * had included them.
     *
     * This resolves the receipt's own pending commitments, so it works
     * regardless of whether a privacy nonce was used.
     */
    public function confirm(Receipt $receipt, ?int $blockHeight = null): void
    {
        foreach ($receipt->detachedTimestampFile()->timestamp->findPending() as ['node' => $node]) {
            $this->confirmed[bin2hex($node->msg)] = $blockHeight ?? $this->defaultBlockHeight;
        }
    }

    /**
     * Confirm every digest submitted so far.
     */
    public function confirmAll(?int $blockHeight = null): void
    {
        foreach (array_keys($this->submitted) as $key) {
            $this->confirmed[$key] = $blockHeight ?? $this->defaultBlockHeight;
        }
    }

    /**
     * Forget all submitted and confirmed state.
     */
    public function reset(): void
    {
        $this->submitted = [];
        $this->confirmed = [];
    }
}
