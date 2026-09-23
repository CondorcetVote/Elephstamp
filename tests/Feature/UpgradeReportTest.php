<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Calendar\{CalendarClient, CalendarResponse, FakeCalendarClient};
use CondorcetVote\ElephStamp\Exception\CalendarException;
use CondorcetVote\ElephStamp\Operation\{Append, Sha256};
use CondorcetVote\ElephStamp\Random\DeterministicRandomSource;
use CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome;
use CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource;
use CondorcetVote\ElephStamp\{ElephStamp, FileToStamp, Receipt, Timestamp};

/**
 * A calendar that accepts every submission, then answers each upgrade poll
 * according to the calendar URL: "down" fails, "evil" forges another digest,
 * "quiet" has nothing yet, anything else confirms at block 700_000.
 */
function scriptedCalendar(): CalendarClient
{
    return new class implements CalendarClient {
        public function submit(array $calendarUrls, string $digest): array
        {
            return array_map(static function (string $url) use ($digest): CalendarResponse {
                $timestamp = new Timestamp($digest);
                $timestamp->addAttestation(new PendingAttestation($url));

                return CalendarResponse::success($url, $timestamp);
            }, $calendarUrls);
        }

        public function getTimestamps(array $requests): array
        {
            return array_map(static function (array $request): CalendarResponse {
                $timestamp = match ($request['url']) {
                    'https://down.example' => null,
                    'https://quiet.example' => null,
                    'https://evil.example' => new Timestamp(hash('sha256', 'evil', binary: true)),
                    default => new Timestamp($request['commitment']),
                };

                if ($request['url'] === 'https://down.example') {
                    return CalendarResponse::failure($request['url'], new CalendarException('connection refused'));
                }

                if ($timestamp === null) {
                    return CalendarResponse::notFound($request['url']);
                }

                $timestamp->addAttestation(new BitcoinAttestation(700_000));

                return CalendarResponse::success($request['url'], $timestamp);
            }, $requests);
        }
    };
}

it('reports one outcome per calendar in proof order', function (): void {
    $blocks = new FakeBlockHeaderSource;
    $client = new ElephStamp(
        calendarClient: scriptedCalendar(),
        calendarUrls: ['https://down.example', 'https://evil.example', 'https://quiet.example', 'https://good.example', 'https://stranger.other'],
        requiredCalendars: 1,
        randomSource: new DeterministicRandomSource,
        upgradeWhitelist: [],
        blockHeaderSource: $blocks,
    );

    $receipt = $client->stamp(FileToStamp::fromContent('report me'));

    // The honest answer attests the commitment itself in block 700000.
    $blocks->addBlock(700_000, $receipt->detachedTimestampFile()->timestamp->findPending()[0]['msg']);

    // Upgrading from a client that stamps elsewhere: the calendars a client
    // stamps with are always allowed, which would hide the skipped one.
    $upgrader = new ElephStamp(
        calendarClient: scriptedCalendar(),
        calendarUrls: ['https://good.example'],
        upgradeWhitelist: ['https://down.example', 'https://evil.example', 'https://quiet.example'],
        blockHeaderSource: $blocks,
    );

    $report = $upgrader->upgradeWithReport($receipt);

    $byUrl = [];

    foreach ($report->results as $result) {
        $byUrl[$result->calendarUrl] = $result;
    }

    expect($report->results)->toHaveCount(5)
        ->and(array_keys($byUrl))->toBe(array_map(
            static fn(array $entry): string => $entry['attestation']->uri,
            $receipt->detachedTimestampFile()->timestamp->findPending(),
        ))
        ->and($byUrl['https://down.example']->outcome)->toBe(UpgradeOutcome::Failed)
        ->and($byUrl['https://down.example']->error)->toContain('connection refused')
        ->and($byUrl['https://evil.example']->outcome)->toBe(UpgradeOutcome::Rejected)
        ->and($byUrl['https://evil.example']->error)->not->toBeNull()
        ->and($byUrl['https://quiet.example']->outcome)->toBe(UpgradeOutcome::Pending)
        ->and($byUrl['https://quiet.example']->error)->toBeNull()
        ->and($byUrl['https://good.example']->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($byUrl['https://stranger.other']->outcome)->toBe(UpgradeOutcome::Skipped)
        ->and($byUrl['https://stranger.other']->outcome->wasContacted())->toBeFalse()
        ->and($byUrl['https://good.example']->commitmentHex())->toBe(bin2hex($byUrl['https://good.example']->commitment))
        ->and($report->changed())->toBeTrue()
        ->and($report->count(UpgradeOutcome::Upgraded))->toBe(1)
        ->and($report->filter(UpgradeOutcome::Skipped))->toHaveCount(1)
        ->and($receipt->isComplete())->toBeTrue()
        ->and($receipt->bitcoinBlockHeight())->toBe(700_000);
});

it('reports a still-pending calendar and no change', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('wait'));

    $report = $client->upgradeWithReport($receipt);

    expect($report->results)->toHaveCount(1)
        ->and($report->results[0]->calendarUrl)->toBe(ElephStamp::FAKE_CALENDAR_URL)
        ->and($report->results[0]->outcome)->toBe(UpgradeOutcome::Pending)
        ->and($report->changed())->toBeFalse();
});

it('reports an unchanged calendar when its answer is already merged', function (): void {
    $calendar = new FakeCalendarClient;
    $client = ElephStamp::fake($calendar);

    // Two calendars are simulated by stamping twice with the same content: the
    // proofs share their commitment, so one upgrade merges what the other
    // already brought. Simpler: merge the same answer twice by hand.
    $receipt = $client->stamp(FileToStamp::fromContent('twice'));
    $calendar->confirm($receipt, 650_000);

    $pending = $receipt->detachedTimestampFile()->timestamp->findPending()[0];
    $answer = new Timestamp($pending['msg']);
    $answer->addAttestation(new BitcoinAttestation(650_000));
    $pending['node']->merge($answer);

    // The receipt is now complete, so the calendar is not polled at all.
    expect($client->upgradeWithReport($receipt)->results)->toBe([]);
});

it('returns an empty report for a complete receipt', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('done'));
    $client->fakeCalendar()->confirmAll();
    $client->upgrade($receipt);

    $report = $client->upgradeWithReport($receipt);

    expect($report->results)->toBe([])
        ->and($report->changed())->toBeFalse();
});

/**
 * Two calendars that fork their own branch under the submitted digest, like
 * real ones, and confirm on demand at distinct block heights.
 */
function forkingCalendar(): CalendarClient
{
    return new class implements CalendarClient {
        /** @var array<string, int> */
        public array $confirmed = [];

        public function submit(array $calendarUrls, string $digest): array
        {
            return array_map(static function (string $url) use ($digest): CalendarResponse {
                $timestamp = new Timestamp($digest);
                $timestamp->addOp(new Append(md5($url, true)))->addOp(new Sha256)->addAttestation(new PendingAttestation($url));

                return CalendarResponse::success($url, $timestamp);
            }, $calendarUrls);
        }

        public function getTimestamps(array $requests): array
        {
            return array_map(function (array $request): CalendarResponse {
                if (!isset($this->confirmed[$request['url']])) {
                    return CalendarResponse::notFound($request['url']);
                }

                $timestamp = new Timestamp($request['commitment']);
                $timestamp->addAttestation(new BitcoinAttestation($this->confirmed[$request['url']]));

                return CalendarResponse::success($request['url'], $timestamp);
            }, $requests);
        }
    };
}

it('leaves a complete receipt alone unless asked to poll all calendars', function (): void {
    $calendar = forkingCalendar();
    $blocks = new FakeBlockHeaderSource;
    $client = new ElephStamp(
        calendarClient: $calendar,
        calendarUrls: ['https://one.example', 'https://two.example'],
        randomSource: new DeterministicRandomSource,
        upgradeWhitelist: [],
        blockHeaderSource: $blocks,
    );

    $receipt = $client->stamp(FileToStamp::fromContent('two branches'));

    // Each calendar attests its own commitment; register the blocks they will name.
    [$one, $two] = $receipt->detachedTimestampFile()->timestamp->findPending();
    $blocks->addBlock(700_010, $one['msg']);
    $blocks->addBlock(700_005, $two['msg']);

    // First calendar confirms: the receipt is complete with one anchor.
    $calendar->confirmed['https://one.example'] = 700_010;
    $first = $client->upgradeWithReport($receipt);

    expect($first->changed())->toBeTrue()
        ->and($receipt->isComplete())->toBeTrue()
        ->and($receipt->bitcoinAnchors())->toHaveCount(1)
        ->and($first->results[1]->outcome)->toBe(UpgradeOutcome::Pending);

    // Second calendar confirms later, in an earlier block: ignored by default…
    $calendar->confirmed['https://two.example'] = 700_005;

    expect($client->upgrade($receipt))->toBeFalse()
        ->and($client->upgradeWithReport($receipt)->results)->toBe([])
        ->and($receipt->bitcoinBlockHeight())->toBe(700_010);

    // …collected with pollAll, without asking the confirmed calendar again.
    $report = $client->upgradeWithReport($receipt, pollAll: true);

    expect($report->changed())->toBeTrue()
        ->and($report->results)->toHaveCount(2)
        ->and($report->results[0]->outcome)->toBe(UpgradeOutcome::Confirmed)
        ->and($report->results[0]->outcome->wasContacted())->toBeFalse()
        ->and($report->results[0]->blockHeight)->toBe(700_010)
        ->and($report->results[1]->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($report->results[1]->blockHeight)->toBe(700_005)
        ->and($receipt->bitcoinAnchors())->toHaveCount(2)
        ->and($receipt->bitcoinBlockHeight())->toBe(700_005)
        ->and(Receipt::fromBytes($receipt->toBytes())->bitcoinAnchors())->toHaveCount(2);

    // Nothing left to fetch: every submission is confirmed, none is polled.
    $again = $client->upgradeWithReport($receipt, pollAll: true);

    expect($again->changed())->toBeFalse()
        ->and($again->count(UpgradeOutcome::Confirmed))->toBe(2);
});

it('reports the block height a still-pending receipt gained from a partial upgrade', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('height'));

    expect($client->upgradeWithReport($receipt)->results[0]->blockHeight)->toBeNull();

    $client->fakeCalendar()->confirmAll(650_000);

    expect($client->upgradeWithReport($receipt)->results[0]->blockHeight)->toBe(650_000);
});

/**
 * A fake client whose calendar has confirmed a receipt, with the chain
 * tampered with afterwards so that the calendar's answer no longer holds up.
 */
function fakeClientWithConfirmedReceipt(string $content, int $height = 800_000): array
{
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent($content));
    $client->fakeCalendar()->confirm($receipt, $height);

    return [$client, $receipt];
}

it('verifies a calendar answer against the blockchain before merging it', function (): void {
    [$client, $receipt] = fakeClientWithConfirmedReceipt('checked');

    $report = $client->upgradeWithReport($receipt);
    $result = $report->results[0];

    expect($result->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($result->verified())->toBeTrue()
        ->and($result->confirmations())->toBe(6)
        ->and($result->claimedBlockHeight())->toBe(800_000)
        ->and($result->blockHeight)->toBe(800_000)
        ->and($result->verifications)->toHaveCount(1)
        ->and($report->blockHeaderSource)->toBe('fake block source')
        ->and($receipt->isComplete())->toBeTrue();
});

it('rejects an answer whose block does not commit to it, and keeps the calendar pending', function (): void {
    [$client, $receipt] = fakeClientWithConfirmedReceipt('forged answer');
    $blocks = $client->fakeBlockSource();

    // The chain says block 800000 holds another merkle root.
    $blocks->reset();
    $blocks->addBlock(800_000, str_repeat("\xee", 32));

    $report = $client->upgradeWithReport($receipt);
    $result = $report->results[0];

    expect($result->outcome)->toBe(UpgradeOutcome::Rejected)
        ->and($result->error)->toContain('block 800000')->toContain('merkle root mismatch')
        ->and($result->verified())->toBeFalse()
        ->and($result->blockHeight)->toBeNull()
        ->and($result->claimedBlockHeight())->toBe(800_000)
        ->and($report->changed())->toBeFalse()
        ->and($receipt->isPending())->toBeTrue()
        ->and($receipt->bitcoinAttestations())->toBe([]);

    // Once the chain agrees with the calendar, the same poll goes through.
    $blocks->reset();
    $blocks->addBlock(800_000, $receipt->detachedTimestampFile()->timestamp->findPending()[0]['msg']);

    expect($client->upgrade($receipt))->toBeTrue()
        ->and($receipt->isComplete())->toBeTrue();
});

it('does not merge an answer whose block is still too shallow', function (): void {
    [$client, $receipt] = fakeClientWithConfirmedReceipt('too fresh');
    $client->fakeBlockSource()->setTipHeight(800_002);

    $result = $client->upgradeWithReport($receipt)->results[0];

    expect($result->outcome)->toBe(UpgradeOutcome::Unconfirmed)
        ->and($result->outcome->wasContacted())->toBeTrue()
        ->and($result->error)->toContain('3 of the 6 confirmations')
        ->and($result->confirmations())->toBe(3)
        ->and($result->verified())->toBeFalse()
        ->and($receipt->isPending())->toBeTrue();

    // A lower threshold accepts it; the chain growing does too.
    expect($client->upgradeWithReport($receipt, requiredConfirmations: 3)->results[0]->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($receipt->isComplete())->toBeTrue();
});

it('does not merge an answer it cannot check', function (): void {
    [$client, $receipt] = fakeClientWithConfirmedReceipt('unknown chain');
    $client->fakeBlockSource()->reset();

    $report = $client->upgradeWithReport($receipt);
    $result = $report->results[0];

    expect($result->outcome)->toBe(UpgradeOutcome::Unverifiable)
        ->and($result->error)->toContain('block 800000')->toContain('no block registered')
        ->and($result->confirmations())->toBeNull()
        ->and($report->blockHeaderSource)->toBe('fake block source')
        ->and($receipt->isPending())->toBeTrue();

    // Tip known, block unknown.
    $client->fakeBlockSource()->setTipHeight(900_000);

    expect($client->upgradeWithReport($receipt)->results[0]->outcome)->toBe(UpgradeOutcome::Unverifiable);
});

it('merges unchecked answers when verification is disabled', function (): void {
    [$client, $receipt] = fakeClientWithConfirmedReceipt('trusting');
    $client->fakeBlockSource()->reset();
    $client->fakeBlockSource()->addBlock(800_000, str_repeat("\xee", 32));

    $report = $client->upgradeWithReport($receipt, verify: false);
    $result = $report->results[0];

    expect($result->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($result->verified())->toBeNull()
        ->and($result->verifications)->toBe([])
        ->and($result->confirmations())->toBeNull()
        ->and($result->claimedBlockHeight())->toBeNull()
        ->and($report->blockHeaderSource)->toBeNull()
        ->and($receipt->isComplete())->toBeTrue()
        ->and($client->verify($receipt)->verdict())->toBe(CondorcetVote\ElephStamp\Verify\Verdict::Failed);
});

it('does not consult the block source when no answer carries a Bitcoin attestation', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('nothing yet'));
    $client->fakeBlockSource()->reset();

    $report = $client->upgradeWithReport($receipt);

    expect($report->results[0]->outcome)->toBe(UpgradeOutcome::Pending)
        ->and($report->blockHeaderSource)->toBeNull();
});

it('keeps the honest calendar and drops the lying one in a single pass', function (): void {
    $calendar = forkingCalendar();
    $blocks = new FakeBlockHeaderSource;
    $client = new ElephStamp(
        calendarClient: $calendar,
        calendarUrls: ['https://honest.example', 'https://liar.example'],
        randomSource: new DeterministicRandomSource,
        upgradeWhitelist: [],
        blockHeaderSource: $blocks,
    );

    $receipt = $client->stamp(FileToStamp::fromContent('mixed'));
    [$honest, $liar] = $receipt->detachedTimestampFile()->timestamp->findPending();
    $blocks->addBlock(700_010, $honest['msg']);
    $blocks->addBlock(700_011, str_repeat("\x11", 32));
    $calendar->confirmed['https://honest.example'] = 700_010;
    $calendar->confirmed['https://liar.example'] = 700_011;

    $report = $client->upgradeWithReport($receipt);

    expect($report->results[0]->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($report->results[1]->outcome)->toBe(UpgradeOutcome::Rejected)
        ->and($receipt->bitcoinAnchors())->toHaveCount(1)
        ->and($receipt->bitcoinBlockHeight())->toBe(700_010)
        ->and($client->verify($receipt)->verdict())->toBe(CondorcetVote\ElephStamp\Verify\Verdict::Verified);

    // The liar is still pending in the proof and would be asked again with pollAll.
    $again = $client->upgradeWithReport($receipt, pollAll: true);

    expect($again->results[0]->outcome)->toBe(UpgradeOutcome::Confirmed)
        ->and($again->results[1]->outcome)->toBe(UpgradeOutcome::Rejected);
});

it('rejects a confirmation threshold below one', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('zero'));

    $client->upgrade($receipt, requiredConfirmations: 0);
})->throws(CondorcetVote\ElephStamp\Exception\InvalidInputException::class, 'requiredConfirmations');

it('contacts the normalized calendar URL and reports the one written in the proof', function (): void {
    $calendar = new class implements CalendarClient {
        /**
         * @var list<string>
         */
        public array $polled = [];

        public function submit(array $calendarUrls, string $digest): array
        {
            return array_map(static function (string $url) use ($digest): CalendarResponse {
                $timestamp = new Timestamp($digest);
                $timestamp->addAttestation(new PendingAttestation('HTTPS://Good.Example:443/'));

                return CalendarResponse::success($url, $timestamp);
            }, $calendarUrls);
        }

        public function getTimestamps(array $requests): array
        {
            return array_map(function (array $request): CalendarResponse {
                $this->polled[] = $request['url'];

                return CalendarResponse::notFound($request['url']);
            }, $requests);
        }
    };

    $client = new ElephStamp(calendarClient: $calendar, calendarUrls: ['https://good.example'], randomSource: new DeterministicRandomSource, upgradeWhitelist: []);
    $report = $client->upgradeWithReport($client->stamp(FileToStamp::fromContent('normalize me')));

    expect($calendar->polled)->toBe(['https://good.example'])
        ->and($report->results[0]->calendarUrl)->toBe('HTTPS://Good.Example:443/')
        ->and($report->results[0]->outcome)->toBe(UpgradeOutcome::Pending);
});
