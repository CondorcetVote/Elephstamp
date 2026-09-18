<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Merkle\MerkleTree;
use CondorcetVote\ElephStamp\Random\{CryptoRandomSource, DeterministicRandomSource, RandomSource};
use CondorcetVote\ElephStamp\Calendar\{CalendarClient, CalendarResponse, CalendarWhitelist, FakeCalendarClient, HttpCalendarClient};
use CondorcetVote\ElephStamp\Exception\{InvalidInputException, SerializationException, StampingException};
use CondorcetVote\ElephStamp\Operation\{Append, HashOperation, Sha256};
use CondorcetVote\ElephStamp\Upgrade\{CalendarUpgradeResult, UpgradeOutcome, UpgradeReport};

/**
 * The library entry point: submit timestamp requests to calendar servers and
 * refresh receipts as they get confirmed.
 *
 * Verification against the Bitcoin blockchain is deliberately out of scope.
 */
final class ElephStamp
{
    /**
     * The public aggregator calendars used by default.
     *
     * @var list<string>
     */
    public const array DEFAULT_CALENDAR_URLS = [
        'https://a.pool.opentimestamps.org',
        'https://b.pool.opentimestamps.org',
        'https://a.pool.eternitywall.com',
        'https://ots.btc.catallaxy.com',
    ];

    /**
     * Host patterns an upgrade is allowed to contact by default.
     *
     * These are the calendars operated by the known OpenTimestamps operators.
     * They guard against a hostile `.ots` pointing upgrades at arbitrary hosts.
     *
     * @var list<string>
     */
    public const array DEFAULT_UPGRADE_WHITELIST = [
        'https://*.calendar.opentimestamps.org',
        'https://*.calendar.eternitywall.com',
        'https://*.calendar.catallaxy.com',
    ];

    /**
     * Calendar URL used by the fake client.
     */
    public const string FAKE_CALENDAR_URL = 'https://fake.calendar.elephstamp';

    /**
     * Length of the per-file privacy nonce, in bytes.
     */
    private const int NONCE_LENGTH = 16;

    private readonly CalendarClient $calendarClient;

    private readonly HashOperation $hashOperation;

    private readonly RandomSource $randomSource;

    private readonly CalendarWhitelist $upgradeWhitelist;

    /**
     * @var list<string>
     */
    private readonly array $calendarUrls;

    private readonly int $requiredCalendars;

    /**
     * @param list<string>|null $calendarUrls      calendars to submit to (defaults to {@see DEFAULT_CALENDAR_URLS})
     * @param int|null          $requiredCalendars minimum number of calendars that must accept a stamp (the "m" of
     *                                             m-of-n); defaults to 2 — like the reference client — or to 1 when
     *                                             a single calendar is configured
     * @param list<string>|null $upgradeWhitelist   host patterns an upgrade may contact (defaults to {@see DEFAULT_UPGRADE_WHITELIST}); pass your own when using private calendars
     */
    public function __construct(
        ?CalendarClient $calendarClient = null,
        ?array $calendarUrls = null,
        ?int $requiredCalendars = null,
        ?HashOperation $hashOperation = null,
        ?RandomSource $randomSource = null,
        ?array $upgradeWhitelist = null,
    ) {
        $this->calendarClient = $calendarClient ?? new HttpCalendarClient;
        $this->calendarUrls = $calendarUrls ?? self::DEFAULT_CALENDAR_URLS;
        $this->hashOperation = $hashOperation ?? new Sha256;
        $this->randomSource = $randomSource ?? new CryptoRandomSource;
        $this->upgradeWhitelist = new CalendarWhitelist($upgradeWhitelist ?? self::DEFAULT_UPGRADE_WHITELIST);
        $this->requiredCalendars = $requiredCalendars ?? min(2, \count($this->calendarUrls));

        if (empty($this->calendarUrls)) {
            throw new InvalidInputException('At least one calendar URL is required');
        }

        foreach ($this->calendarUrls as $url) {
            if (!str_starts_with($url, 'https://')) {
                throw new InvalidInputException(\sprintf('Calendar URLs must use https, got: %s', $url));
            }
        }

        // A duplicated URL would count several times toward requiredCalendars,
        // silently voiding the m-of-n redundancy policy.
        $normalizedUrls = array_map(static fn(string $url): string => strtolower(rtrim($url, '/')), $this->calendarUrls);

        if (\count(array_unique($normalizedUrls)) !== \count($normalizedUrls)) {
            throw new InvalidInputException('Calendar URLs must be unique');
        }

        if ($this->requiredCalendars < 1 || $this->requiredCalendars > \count($this->calendarUrls)) {
            throw new InvalidInputException(\sprintf(
                'requiredCalendars must be between 1 and the number of calendars (%d); got %d',
                \count($this->calendarUrls),
                $this->requiredCalendars,
            ));
        }
    }

    /**
     * Build a fully offline, deterministic client for tests and local environments.
     *
     * Pass the same {@see FakeCalendarClient} to several calls to share its
     * confirmation state, or read it back with {@see fakeCalendar()}.
     */
    public static function fake(?FakeCalendarClient $calendar = null): self
    {
        return new self(
            calendarClient: $calendar ?? new FakeCalendarClient,
            calendarUrls: [self::FAKE_CALENDAR_URL],
            randomSource: new DeterministicRandomSource,
            upgradeWhitelist: [self::FAKE_CALENDAR_URL],
        );
    }

    /**
     * The fake calendar backing this client, for driving its lifecycle in tests.
     *
     * @throws InvalidInputException if this client is not in fake mode
     */
    public function fakeCalendar(): FakeCalendarClient
    {
        if (!$this->calendarClient instanceof FakeCalendarClient) {
            throw new InvalidInputException('This client is not backed by a fake calendar');
        }

        return $this->calendarClient;
    }

    /**
     * Timestamp a single file.
     *
     * @throws StampingException if too few calendars accept the request
     */
    public function stamp(FileToStamp $file): Receipt
    {
        return $this->stampMany($file)[0];
    }

    /**
     * Timestamp several files at once, sharing a single calendar submission.
     *
     * All files are bound to one merkle tree, so a single commitment covers
     * them; each file still gets its own independent receipt. The receipts of
     * a batch share their tree nodes in memory: upgrading one also refreshes
     * its siblings, until they are reloaded from disk.
     *
     * Privacy: the merkle tree embeds each leaf's message into the proofs of
     * its neighbours. A file stamped {@see FileToStamp::withoutNonce()} in a
     * batch therefore exposes its plain digest to whoever holds a sibling
     * receipt, in addition to the calendars.
     *
     * @throws StampingException if too few calendars accept the request
     *
     * @return list<Receipt>
     */
    public function stampMany(FileToStamp ...$files): array
    {
        if (empty($files)) {
            throw new InvalidInputException('At least one file is required');
        }

        $fileTimestamps = [];
        $merkleLeaves = [];

        foreach ($files as $file) {
            $fileTimestamp = new Timestamp($file->digest($this->hashOperation));
            $fileTimestamps[] = $fileTimestamp;
            $merkleLeaves[] = $this->merkleLeaf($fileTimestamp, $file->useNonce);
        }

        $merkleTip = MerkleTree::build($merkleLeaves);

        $this->submitToCalendars($merkleTip);

        return array_map(
            fn(Timestamp $timestamp): Receipt => new Receipt(new DetachedTimestampFile($this->hashOperation, $timestamp)),
            $fileTimestamps,
        );
    }

    /**
     * Query the calendars for confirmations and merge them into the receipt.
     *
     * Performs a single polling pass and returns whether anything changed; call
     * it again later to keep polling a still-pending receipt. Use
     * {@see upgradeWithReport()} to learn what each calendar answered.
     *
     * @param bool $pollAll also poll the calendars still pending in an already complete receipt, to collect every attestation rather than stopping at the first
     */
    public function upgrade(Receipt $receipt, bool $pollAll = false): bool
    {
        return $this->upgradeWithReport($receipt, $pollAll)->changed();
    }

    /**
     * Like {@see upgrade()}, but returns what happened with every calendar.
     *
     * The report lists one entry per pending attestation of the receipt, in
     * proof order: upgraded, still pending, failed, rejected, or skipped
     * because its calendar is not whitelisted.
     *
     * One Bitcoin attestation makes a receipt complete and verifiable, so by
     * default a complete receipt is not polled and yields an empty report.
     * With $pollAll the calendars still pending in a complete receipt are
     * polled too, and the submissions already confirmed are reported as such.
     */
    public function upgradeWithReport(Receipt $receipt, bool $pollAll = false): UpgradeReport
    {
        // A complete receipt has nothing left to poll for.
        if ($receipt->isComplete() && !$pollAll) {
            return new UpgradeReport([]);
        }

        $results = [];
        $toPoll = [];

        // Only contact calendars whose URI is whitelisted: an untrusted `.ots`
        // must not be able to point us at arbitrary hosts.
        foreach ($receipt->detachedTimestampFile()->timestamp->findPending() as $index => $entry) {
            if ($entry['node']->hasBitcoinAttestation()) {
                $results[$index] = new CalendarUpgradeResult($entry['attestation']->uri, $entry['msg'], UpgradeOutcome::Confirmed, blockHeight: self::lowestBlockHeight($entry['node']));
            } elseif ($this->upgradeWhitelist->allows($entry['attestation']->uri)) {
                $toPoll[$index] = $entry;
            } else {
                $results[$index] = new CalendarUpgradeResult($entry['attestation']->uri, $entry['msg'], UpgradeOutcome::Skipped);
            }
        }

        if (!empty($toPoll)) {
            $requests = array_values(array_map(
                static fn(array $entry): array => ['url' => $entry['attestation']->uri, 'commitment' => $entry['msg']],
                $toPoll,
            ));
            $indexes = array_keys($toPoll);

            // Responses come back aligned with $requests; a calendar that is
            // unreachable or still has nothing simply yields no timestamp and
            // is reported as such, never aborting the pass.
            foreach ($this->calendarClient->getTimestamps($requests) as $position => $response) {
                $index = $indexes[$position];
                $results[$index] = $this->mergeUpgradeResponse($toPoll[$index], $response);
            }
        }

        ksort($results);

        return new UpgradeReport(array_values($results));
    }

    /**
     * @param array{node: Timestamp, msg: string, attestation: PendingAttestation} $pending
     */
    private function mergeUpgradeResponse(array $pending, CalendarResponse $response): CalendarUpgradeResult
    {
        $url = $pending['attestation']->uri;
        $commitment = $pending['msg'];

        if ($response->timestamp === null) {
            if ($response->error !== null) {
                return new CalendarUpgradeResult($url, $commitment, UpgradeOutcome::Failed, $response->error->getMessage());
            }

            return new CalendarUpgradeResult($url, $commitment, UpgradeOutcome::Pending);
        }

        try {
            $changed = $pending['node']->merge($response->timestamp);
        } catch (SerializationException $exception) {
            // A timestamp that does not commit to the digest we asked for is
            // hostile or corrupt: skip that calendar, keep the pass.
            return new CalendarUpgradeResult($url, $commitment, UpgradeOutcome::Rejected, $exception->getMessage());
        }

        return new CalendarUpgradeResult(
            $url,
            $commitment,
            $changed ? UpgradeOutcome::Upgraded : UpgradeOutcome::Unchanged,
            blockHeight: self::lowestBlockHeight($pending['node']),
        );
    }

    private static function lowestBlockHeight(Timestamp $node): ?int
    {
        $heights = [];

        foreach ($node->allAttestations() as ['attestation' => $attestation]) {
            if ($attestation instanceof BitcoinAttestation) {
                $heights[] = $attestation->blockHeight;
            }
        }

        return $heights === [] ? null : min($heights);
    }

    private function merkleLeaf(Timestamp $fileTimestamp, bool $useNonce): Timestamp
    {
        if (!$useNonce) {
            return $fileTimestamp;
        }

        $nonced = $fileTimestamp->addOp(new Append($this->randomSource->bytes(self::NONCE_LENGTH)));

        return $nonced->addOp(new Sha256);
    }

    private function submitToCalendars(Timestamp $merkleTip): void
    {
        // The tip always descends from real file digests, never from an
        // unverifiable subtree.
        \assert($merkleTip->msg !== null);

        $merged = 0;
        $errors = [];

        // All calendars are contacted concurrently; each response is either a
        // pending timestamp we merge, or a failure we tolerate as long as
        // enough others succeed. The threshold is enforced below.
        foreach ($this->calendarClient->submit($this->calendarUrls, $merkleTip->msg) as $response) {
            if ($response->timestamp !== null) {
                // An honest calendar always answers a submission with pending
                // attestations only: Bitcoin anchoring happens later, through
                // upgrade(). Anything else is a forgery that would fake an
                // instantly-complete receipt.
                if (!self::onlyPendingAttestations($response->timestamp)) {
                    $errors[] = $response->calendarUrl . ': submission response contained a non-pending attestation';

                    continue;
                }

                $merkleTip->merge($response->timestamp);
                ++$merged;
            } elseif ($response->error !== null) {
                $errors[] = $response->calendarUrl . ': ' . $response->error->getMessage();
            }
        }

        if ($merged < $this->requiredCalendars) {
            throw new StampingException(\sprintf(
                'Only %d of the required %d calendars accepted the timestamp%s',
                $merged,
                $this->requiredCalendars,
                empty($errors) ? '' : ' (' . implode('; ', $errors) . ')',
            ));
        }
    }

    private static function onlyPendingAttestations(Timestamp $timestamp): bool
    {
        foreach ($timestamp->allAttestations() as ['attestation' => $attestation]) {
            if (!$attestation instanceof PendingAttestation) {
                return false;
            }
        }

        return true;
    }
}
