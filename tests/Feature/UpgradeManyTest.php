<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Calendar\FakeCalendarClient;
use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Random\DeterministicRandomSource;
use CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome;
use CondorcetVote\ElephStamp\{ElephStamp, FileToStamp, Receipt};
use Tests\Support\{CountingBlockHeaderSource, CountingCalendarClient};

/**
 * A fake-mode client whose calendar polls and block header requests are counted.
 *
 * @return array{ElephStamp, FakeCalendarClient, CountingCalendarClient, CountingBlockHeaderSource}
 */
function countingFakeClient(): array
{
    $fake = new FakeCalendarClient;
    $calendar = new CountingCalendarClient($fake);
    $blocks = new CountingBlockHeaderSource($fake->blocks());

    $client = new ElephStamp(
        calendarClient: $calendar,
        calendarUrls: [ElephStamp::FAKE_CALENDAR_URL],
        randomSource: new DeterministicRandomSource,
        upgradeWhitelist: [],
        blockHeaderSource: $blocks,
    );

    return [$client, $fake, $calendar, $blocks];
}

/**
 * A copy of the receipt as read back from disk: its own tree, shared with nobody.
 */
function reloaded(Receipt $receipt): Receipt
{
    return Receipt::fromBytes($receipt->toBytes());
}

it('polls a calendar once for every receipt of a batch', function (): void {
    [$client, $fake, $calendar, $blocks] = countingFakeClient();

    $receipts = array_map(reloaded(...), $client->stampMany(
        FileToStamp::fromContent('batch A'),
        FileToStamp::fromContent('batch B'),
        FileToStamp::fromContent('batch C'),
    ));
    $fake->confirmAll(800_000);

    $reports = $client->upgradeMany($receipts);

    expect($reports)->toHaveCount(3)
        ->and($calendar->polls)->toHaveCount(1)
        ->and($calendar->requests())->toHaveCount(1)
        ->and($blocks->tipRequests)->toBe(1)
        ->and($blocks->headerRequests)->toBe([800_000]);

    foreach ($reports as $index => $report) {
        expect($report->changed())->toBeTrue()
            ->and($report->results)->toHaveCount(1)
            ->and($report->results[0]->outcome)->toBe(UpgradeOutcome::Upgraded)
            ->and($report->results[0]->blockHeight)->toBe(800_000)
            ->and($report->results[0]->verified())->toBeTrue()
            ->and($report->blockHeaderSource)->toBe('fake block source')
            ->and($receipts[$index]->isComplete())->toBeTrue()
            ->and($receipts[$index]->bitcoinBlockHeight())->toBe(800_000);
    }
});

it('keeps unrelated receipts apart and reports each on its own', function (): void {
    [$client, $fake, $calendar, $blocks] = countingFakeClient();

    $confirmed = reloaded($client->stamp(FileToStamp::fromContent('confirmed')));
    $fake->confirm($confirmed, 800_000);

    $complete = $client->stamp(FileToStamp::fromContent('already complete'));
    $fake->confirm($complete, 800_001);
    $client->upgrade($complete);

    $stillPending = reloaded($client->stamp(FileToStamp::fromContent('nothing yet')));

    // Only count the pass under test, not the upgrade that completed $complete.
    $calendar->polls = [];
    $blocks->tipRequests = 0;
    $blocks->headerRequests = [];

    $reports = $client->upgradeMany([$confirmed, $complete, $stillPending]);

    // One request per distinct commitment; the complete receipt is not polled.
    expect($calendar->polls)->toHaveCount(1)
        ->and($calendar->requests())->toHaveCount(2)
        ->and($blocks->tipRequests)->toBe(1)
        ->and($blocks->headerRequests)->toBe([800_000])
        ->and($reports)->toHaveCount(3)
        ->and($reports[0]->results[0]->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($reports[0]->results[0]->blockHeight)->toBe(800_000)
        ->and($reports[0]->blockHeaderSource)->toBe('fake block source')
        ->and($confirmed->isComplete())->toBeTrue()
        ->and($reports[1]->results)->toBe([])
        ->and($reports[1]->blockHeaderSource)->toBeNull()
        ->and($reports[2]->results[0]->outcome)->toBe(UpgradeOutcome::Pending)
        ->and($reports[2]->changed())->toBeFalse()
        // Nothing of this receipt was checked, so no source is reported for it.
        ->and($reports[2]->blockHeaderSource)->toBeNull()
        ->and($stillPending->isPending())->toBeTrue();
});

it('fetches each block once when unrelated receipts sit in different blocks', function (): void {
    [$client, $fake, $calendar, $blocks] = countingFakeClient();

    $first = reloaded($client->stamp(FileToStamp::fromContent('first')));
    $fake->confirm($first, 800_000);

    $seconds = array_map(reloaded(...), $client->stampMany(FileToStamp::fromContent('second'), FileToStamp::fromContent('third')));
    $fake->confirm($seconds[0], 800_005);

    $reports = $client->upgradeMany([$first, ...$seconds]);

    expect($calendar->requests())->toHaveCount(2)
        ->and($blocks->tipRequests)->toBe(1)
        ->and($blocks->headerRequests)->toBe([800_000, 800_005])
        ->and(array_map(static fn($report): ?int => $report->results[0]->blockHeight, $reports))->toBe([800_000, 800_005, 800_005]);
});

it('reports the gain on every in-memory sibling of a batch', function (): void {
    $client = ElephStamp::fake();

    // Not reloaded: the receipts still share their tree nodes.
    $receipts = $client->stampMany(FileToStamp::fromContent('sibling A'), FileToStamp::fromContent('sibling B'));
    $client->fakeCalendar()->confirmAll();

    $reports = $client->upgradeMany($receipts);

    expect($reports[0]->changed())->toBeTrue()
        ->and($reports[1]->changed())->toBeTrue()
        ->and($reports[1]->results[0]->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($receipts[0]->isComplete())->toBeTrue()
        ->and($receipts[1]->isComplete())->toBeTrue();
});

it('merges each answer where its receipt is pending, even when the answers differ', function (): void {
    [$client, $fake] = countingFakeClient();

    $a = reloaded($client->stamp(FileToStamp::fromContent('a')));
    $b = reloaded($client->stamp(FileToStamp::fromContent('b')));
    $fake->confirm($a, 800_000);
    $fake->confirm($b, 800_010);

    [$reportA, $reportB] = $client->upgradeMany([$a, $b]);

    expect($a->bitcoinBlockHeight())->toBe(800_000)
        ->and($b->bitcoinBlockHeight())->toBe(800_010)
        ->and($reportA->results[0]->blockHeight)->toBe(800_000)
        ->and($reportB->results[0]->blockHeight)->toBe(800_010);
});

it('does nothing for no receipts', function (): void {
    [$client, , $calendar, $blocks] = countingFakeClient();

    expect($client->upgradeMany([]))->toBe([])
        ->and($calendar->polls)->toBe([])
        ->and($blocks->tipRequests)->toBe(0);
});

it('rejects a confirmation threshold below one', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('x'));

    expect(fn() => $client->upgradeMany([$receipt], requiredConfirmations: 0))->toThrow(InvalidInputException::class);
});

it('is what upgradeWithReport runs on a single receipt', function (): void {
    [$client, $fake, $calendar] = countingFakeClient();

    $receipt = reloaded($client->stamp(FileToStamp::fromContent('single')));
    $fake->confirm($receipt);

    $report = $client->upgradeWithReport($receipt);

    expect($calendar->polls)->toHaveCount(1)
        ->and($report->results[0]->outcome)->toBe(UpgradeOutcome::Upgraded)
        ->and($receipt->isComplete())->toBeTrue();
});
