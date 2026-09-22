<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Bitcoin\AnchorLocator;
use CondorcetVote\ElephStamp\Merkle\MerkleTree;
use CondorcetVote\ElephStamp\Random\{CryptoRandomSource, DeterministicRandomSource, RandomSource};
use CondorcetVote\ElephStamp\Calendar\{CalendarClient, CalendarResponse, CalendarWhitelist, FakeCalendarClient, HttpCalendarClient};
use CondorcetVote\ElephStamp\Exception\{InvalidInputException, SerializationException, StampingException};
use CondorcetVote\ElephStamp\Operation\{Append, HashOperation, Sha256};
use CondorcetVote\ElephStamp\Upgrade\{CalendarUpgradeResult, UpgradeOutcome, UpgradeReport};
use CondorcetVote\ElephStamp\Verify\{AnchorOutcome, AnchorVerification, BlockHeaderSource, Explorer, FakeBlockHeaderSource, VerificationReport, Verifier};

/**
 * The library entry point: submit timestamp requests to calendar servers,
 * refresh receipts as they get confirmed, and verify them against block
 * headers obtained from a {@see BlockHeaderSource}.
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

    private ?BlockHeaderSource $blockHeaderSource;

    /**
     * @param list<string>|null $calendarUrls      calendars to submit to (defaults to {@see DEFAULT_CALENDAR_URLS})
     * @param int|null          $requiredCalendars minimum number of calendars that must accept a stamp (the "m" of
     *                                             m-of-n); defaults to 2 — like the reference client — or to 1 when
     *                                             a single calendar is configured
     * @param list<string>|null $upgradeWhitelist   host patterns an upgrade may contact (defaults to {@see DEFAULT_UPGRADE_WHITELIST}); pass your own when using private calendars
     * @param BlockHeaderSource|null $blockHeaderSource where {@see verify()} gets block headers; defaults to the {@see Explorer::DEFAULT} explorer
     */
    public function __construct(
        ?CalendarClient $calendarClient = null,
        ?array $calendarUrls = null,
        ?int $requiredCalendars = null,
        ?HashOperation $hashOperation = null,
        ?RandomSource $randomSource = null,
        ?array $upgradeWhitelist = null,
        ?BlockHeaderSource $blockHeaderSource = null,
    ) {
        $this->blockHeaderSource = $blockHeaderSource;
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
     * confirmation state, or read it back with {@see fakeCalendar()}. The
     * fake calendar mines every confirmation into its
     * {@see FakeCalendarClient::blocks()}, which is the block source this
     * client verifies against, so a confirmed receipt upgrades and verifies
     * without further setup.
     *
     * @throws InvalidInputException if both a calendar and a block source are given and the calendar does not mine into that source
     */
    public static function fake(?FakeCalendarClient $calendar = null, ?FakeBlockHeaderSource $blockHeaderSource = null): self
    {
        $calendar ??= new FakeCalendarClient(blocks: $blockHeaderSource);

        // The fake calendar mines its confirmations into a fake chain; the
        // client must verify against that same chain for upgrades to pass.
        if ($blockHeaderSource !== null && $calendar->blocks() !== $blockHeaderSource) {
            throw new InvalidInputException('The fake calendar mines into another block source than the one given; build the calendar with that source (new FakeCalendarClient(blocks: $source))');
        }

        return new self(
            calendarClient: $calendar,
            calendarUrls: [self::FAKE_CALENDAR_URL],
            randomSource: new DeterministicRandomSource,
            upgradeWhitelist: [self::FAKE_CALENDAR_URL],
            blockHeaderSource: $calendar->blocks(),
        );
    }

    /**
     * The fake block source backing this client, to register the blocks a
     * receipt should verify against.
     *
     * @throws InvalidInputException if this client is not in fake mode
     */
    public function fakeBlockSource(): FakeBlockHeaderSource
    {
        if (!$this->blockHeaderSource instanceof FakeBlockHeaderSource) {
            throw new InvalidInputException('This client is not backed by a fake block source');
        }

        return $this->blockHeaderSource;
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
     * By default every Bitcoin attestation a calendar returns is checked
     * against the blockchain before it is merged, see {@see upgradeWithReport()}.
     *
     * @param bool $pollAll               also poll the calendars still pending in an already complete receipt, to collect every attestation rather than stopping at the first
     * @param bool $verify                check each calendar's Bitcoin attestations against the block header source before merging them
     * @param int  $requiredConfirmations depth a block needs before its attestation is merged, when verifying
     */
    public function upgrade(Receipt $receipt, bool $pollAll = false, bool $verify = true, int $requiredConfirmations = Verifier::DEFAULT_REQUIRED_CONFIRMATIONS): bool
    {
        return $this->upgradeWithReport($receipt, $pollAll, $verify, $requiredConfirmations)->changed();
    }

    /**
     * Like {@see upgrade()}, but returns what happened with every calendar.
     *
     * The report lists one entry per pending attestation of the receipt, in
     * proof order: upgraded, still pending, failed, rejected, unconfirmed,
     * unverifiable, or skipped because its calendar is not whitelisted.
     *
     * A calendar's answer is untrusted: a wrong or hostile calendar could
     * hand out an attestation naming a block that does not commit to the
     * proof, or a block too recent to be final, and the receipt would be
     * "complete" with a proof that fails verification. So by default every
     * Bitcoin attestation in an answer is verified exactly like
     * {@see verify()} does, through the configured {@see BlockHeaderSource},
     * before anything is merged: an answer whose block does not match is
     * {@see UpgradeOutcome::Rejected}, one whose block is still too shallow
     * is {@see UpgradeOutcome::Unconfirmed}, one that could not be checked
     * is {@see UpgradeOutcome::Unverifiable}; none of them is merged, and
     * the calendar stays pending to be polled again. Only fully verified
     * answers become part of the receipt. Pass $verify false to merge
     * whatever the calendars return, as the reference client does.
     *
     * One Bitcoin attestation makes a receipt complete and verifiable, so by
     * default a complete receipt is not polled and yields an empty report.
     * With $pollAll the calendars still pending in a complete receipt are
     * polled too, and the submissions already confirmed are reported as such.
     *
     * @param bool $pollAll               also poll the calendars still pending in an already complete receipt
     * @param bool $verify                check each calendar's Bitcoin attestations against the block header source before merging them
     * @param int  $requiredConfirmations depth a block needs before its attestation is merged, when verifying
     *
     * @throws InvalidInputException if $requiredConfirmations is below one
     */
    public function upgradeWithReport(Receipt $receipt, bool $pollAll = false, bool $verify = true, int $requiredConfirmations = Verifier::DEFAULT_REQUIRED_CONFIRMATIONS): UpgradeReport
    {
        if ($requiredConfirmations < 1) {
            throw new InvalidInputException('requiredConfirmations must be at least 1');
        }

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

        $blockHeaderSource = null;

        if (!empty($toPoll)) {
            $requests = array_values(array_map(
                static fn(array $entry): array => ['url' => $entry['attestation']->uri, 'commitment' => $entry['msg']],
                $toPoll,
            ));
            $indexes = array_keys($toPoll);

            // Responses come back aligned with $requests; a calendar that is
            // unreachable or still has nothing simply yields no timestamp and
            // is reported as such, never aborting the pass.
            $responses = $this->calendarClient->getTimestamps($requests);
            $verifications = $verify ? $this->verifyResponses($responses, $requiredConfirmations, $blockHeaderSource) : array_fill(0, \count($responses), []);

            foreach ($responses as $position => $response) {
                $index = $indexes[$position];
                $results[$index] = $this->mergeUpgradeResponse($toPoll[$index], $response, $verifications[$position], $requiredConfirmations);
            }
        }

        ksort($results);

        return new UpgradeReport(array_values($results), $blockHeaderSource);
    }

    /**
     * Check the Bitcoin attestations of every calendar answer in one go, so
     * the chain tip is fetched once for the whole pass. The source is only
     * consulted, and its description only set, when an answer carries a
     * Bitcoin attestation.
     *
     * @param list<CalendarResponse> $responses
     *
     * @return list<list<AnchorVerification>> aligned with $responses
     */
    private function verifyResponses(array $responses, int $requiredConfirmations, ?string &$blockHeaderSource): array
    {
        $anchors = [];
        $owners = [];

        foreach ($responses as $position => $response) {
            if ($response->timestamp === null) {
                continue;
            }

            foreach (AnchorLocator::locate($response->timestamp) as $anchor) {
                $anchors[] = $anchor;
                $owners[] = $position;
            }
        }

        $verifications = array_fill(0, \count($responses), []);

        if ($anchors === []) {
            return $verifications;
        }

        $source = $this->blockHeaderSource();
        $blockHeaderSource = $source->describe();

        foreach (new Verifier($source, $requiredConfirmations)->checkAnchors($anchors) as $i => $verification) {
            $verifications[$owners[$i]][] = $verification;
        }

        return $verifications;
    }

    /**
     * @param array{node: Timestamp, msg: string, attestation: PendingAttestation} $pending
     * @param list<AnchorVerification>                                              $verifications how the answer's Bitcoin attestations fared, empty when unchecked
     */
    private function mergeUpgradeResponse(array $pending, CalendarResponse $response, array $verifications, int $requiredConfirmations): CalendarUpgradeResult
    {
        $url = $pending['attestation']->uri;
        $commitment = $pending['msg'];

        if ($response->timestamp === null) {
            if ($response->error !== null) {
                return new CalendarUpgradeResult($url, $commitment, UpgradeOutcome::Failed, $response->error->getMessage());
            }

            return new CalendarUpgradeResult($url, $commitment, UpgradeOutcome::Pending);
        }

        // Worst outcome wins, and nothing is merged unless every attestation
        // in the answer is verified: a partially good answer is still not
        // something to build a proof on.
        $unverified = self::unverifiedOutcome($verifications, $requiredConfirmations);

        if ($unverified !== null) {
            [$outcome, $error] = $unverified;

            return new CalendarUpgradeResult($url, $commitment, $outcome, $error, verifications: $verifications);
        }

        try {
            $changed = $pending['node']->merge($response->timestamp);
        } catch (SerializationException $exception) {
            // A timestamp that does not commit to the digest we asked for is
            // hostile or corrupt: skip that calendar, keep the pass.
            return new CalendarUpgradeResult($url, $commitment, UpgradeOutcome::Rejected, $exception->getMessage(), verifications: $verifications);
        }

        return new CalendarUpgradeResult(
            $url,
            $commitment,
            $changed ? UpgradeOutcome::Upgraded : UpgradeOutcome::Unchanged,
            blockHeight: self::lowestBlockHeight($pending['node']),
            verifications: $verifications,
        );
    }

    /**
     * Why an answer must not be merged, or null when every attestation in it
     * is verified (or nothing was checked).
     *
     * @param list<AnchorVerification> $verifications
     *
     * @return array{UpgradeOutcome, string}|null the outcome and its reason
     */
    private static function unverifiedOutcome(array $verifications, int $requiredConfirmations): ?array
    {
        $unconfirmed = null;
        $unverifiable = null;

        foreach ($verifications as $verification) {
            switch ($verification->outcome) {
                case AnchorOutcome::MerkleRootMismatch:
                    return [UpgradeOutcome::Rejected, \sprintf('Bitcoin block %d does not commit to the calendar\'s proof (merkle root mismatch)', $verification->blockHeight())];
                case AnchorOutcome::AwaitingConfirmations:
                    $unconfirmed ??= \sprintf('Bitcoin block %d has %d of the %d confirmations required', $verification->blockHeight(), $verification->confirmations ?? 0, $requiredConfirmations);

                    break;
                case AnchorOutcome::BlockUnavailable:
                    $unverifiable ??= \sprintf('Bitcoin block %d could not be checked: %s', $verification->blockHeight(), $verification->error ?? 'block header unavailable');

                    break;
                case AnchorOutcome::NotComputable:
                    $unverifiable ??= \sprintf('the attestation for Bitcoin block %d sits below an operation this library cannot compute', $verification->blockHeight());

                    break;
                case AnchorOutcome::Verified:
                    break;
            }
        }

        if ($unverifiable !== null) {
            return [UpgradeOutcome::Unverifiable, $unverifiable];
        }

        if ($unconfirmed !== null) {
            return [UpgradeOutcome::Unconfirmed, $unconfirmed];
        }

        return null;
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

    /**
     * Check a receipt against the blockchain, through the configured block
     * header source, and optionally that it is the proof of the given file.
     *
     * See {@see Verifier} for what is checked and what the verdict means.
     *
     * @param int $requiredConfirmations depth a block needs before its attestation counts as final
     */
    public function verify(Receipt $receipt, ?FileToStamp $file = null, int $requiredConfirmations = Verifier::DEFAULT_REQUIRED_CONFIRMATIONS): VerificationReport
    {
        return new Verifier($this->blockHeaderSource(), $requiredConfirmations)->verify($receipt, $file);
    }

    /**
     * Built lazily so that stamping, and upgrading with verification off or
     * with nothing to check, never touch an explorer.
     */
    private function blockHeaderSource(): BlockHeaderSource
    {
        return $this->blockHeaderSource ??= Explorer::DEFAULT->source();
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
        if ($merkleTip->msg === null) {
            throw new StampingException('The merkle tip has no computable message');
        }

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
