<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Calendar\FakeCalendarClient;
use CondorcetVote\ElephStamp\Console\Application;
use CondorcetVote\ElephStamp\Console\Command\UpgradeCommand;
use CondorcetVote\ElephStamp\{ElephStamp, FileToStamp, Receipt};
use Symfony\Component\Console\Tester\ApplicationTester;
use Tests\Feature\Console\FakeClientFactory;

beforeEach(function (): void {
    $this->dir = makeTempDir();
    $this->calendar = new FakeCalendarClient;
    $this->factory = new FakeClientFactory($this->calendar);

    $application = new Application($this->factory);
    $application->setAutoExit(false);
    $this->tester = new ApplicationTester($application);

    $client = $this->factory->create(new CondorcetVote\ElephStamp\Console\ClientOptions);
    $this->receipt = $client->stamp(FileToStamp::fromContent('upgrade me'));
    $this->path = $this->dir . '/doc.ots';
    $this->receipt->saveToPath($this->path);
});

it('reports a still-pending calendar and exits with the pending code', function (): void {
    $before = file_get_contents($this->path);

    $status = $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    expect($status)->toBe(UpgradeCommand::STILL_PENDING)
        ->and($this->tester->getDisplay())->toContain(ElephStamp::FAKE_CALENDAR_URL)
        ->toContain('still pending')
        ->toContain('Still pending, nothing new to save')
        ->and(file_get_contents($this->path))->toBe($before);
});

it('saves the completed proof once the calendar confirms', function (): void {
    $this->calendar->confirmAll(812_345);

    $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain('upgraded')
        ->toContain('Bitcoin block 812345')
        ->and(unwrapped($this->tester->getDisplay()))->toContain(unwrapped('Saved to ' . $this->path))
        ->and(Receipt::fromPath($this->path)->bitcoinBlockHeight())->toBe(812_345);
});

it('polls without writing in dry-run mode', function (): void {
    $this->calendar->confirmAll();
    $before = file_get_contents($this->path);

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--dry-run' => true]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain('Not saved (--dry-run)')
        ->and(file_get_contents($this->path))->toBe($before);
});

it('writes to --output instead of in place', function (): void {
    $this->calendar->confirmAll();
    $before = file_get_contents($this->path);
    $target = $this->dir . '/complete.ots';

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--output' => $target]);

    $this->tester->assertCommandIsSuccessful();

    expect(file_get_contents($this->path))->toBe($before)
        ->and(Receipt::fromPath($target)->isComplete())->toBeTrue();
});

it('refuses --output with several proofs', function (): void {
    $this->tester->run(['upgrade', 'receipts' => [$this->path, $this->path], '--output' => $this->dir . '/x.ots']);

    $this->tester->assertCommandFailed();

    expect($this->tester->getDisplay())->toContain('--output only makes sense with a single proof');
});

it('explains that an already complete proof has nothing to poll', function (): void {
    $this->calendar->confirmAll();
    $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain('Already')
        ->toContain('nothing to poll')
        ->toContain('--all');
});

it('keeps polling a complete proof with --all and reports confirmed submissions', function (): void {
    $this->calendar->confirmAll(812_000);
    $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--all' => true]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('confirmed')
        ->toContain('already anchored in Bitcoin block 812000, not polled')
        ->toContain('nothing new from the other calendars')
        ->not->toContain('Already complete');

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--all' => true, '--json' => true]);

    $document = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($document['was_already_complete'])->toBeTrue()
        ->and($document['changed'])->toBeFalse()
        ->and($document['calendars'][0]['outcome'])->toBe('confirmed')
        ->and($document['calendars'][0]['block_height'])->toBe(812_000);
});

it('skips a calendar outside the whitelist and says why', function (): void {
    $this->calendar->confirmAll();

    $status = $this->tester->run(['upgrade', 'receipts' => [$this->path], '--no-default-whitelist' => true]);

    expect($status)->toBe(UpgradeCommand::STILL_PENDING)
        ->and($this->tester->getDisplay())->toContain('skipped')
        ->toContain('not on the whitelist')
        ->and(Receipt::fromPath($this->path)->isPending())->toBeTrue();

    // Re-allowing the calendar explicitly makes the upgrade go through.
    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--no-default-whitelist' => true, '--whitelist' => [ElephStamp::FAKE_CALENDAR_URL]]);

    $this->tester->assertCommandIsSuccessful();
});

it('reports an unreadable proof, continues with the others, and fails', function (): void {
    $this->calendar->confirmAll();

    $this->tester->run(['upgrade', 'receipts' => [$this->dir . '/missing.ots', $this->path]]);

    $this->tester->assertCommandFailed();

    expect(unwrapped($this->tester->getDisplay()))->toContain('missing.ots')
        ->and($this->tester->getDisplay())->toContain('Summary')
        ->toContain('1 complete')
        ->toContain('1 in error')
        ->and(Receipt::fromPath($this->path)->isComplete())->toBeTrue();
});

it('summarises several proofs', function (): void {
    $other = $this->dir . '/other.ots';
    $this->factory->create(new CondorcetVote\ElephStamp\Console\ClientOptions)->stamp(FileToStamp::fromContent('other'))->saveToPath($other);
    $this->calendar->confirm($this->receipt);

    $status = $this->tester->run(['upgrade', 'receipts' => [$this->path, $other]]);

    expect($status)->toBe(UpgradeCommand::STILL_PENDING)
        ->and($this->tester->getDisplay())->toContain('2 proofs: 1 complete, 1 still pending');
});

it('emits JSON with one entry per calendar', function (): void {
    $this->calendar->confirmAll(700_001);

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--json' => true]);

    $this->tester->assertCommandIsSuccessful();

    $document = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($document['status'])->toBe('complete')
        ->and($document['bitcoin_block_height'])->toBe(700_001)
        ->and($document['changed'])->toBeTrue()
        ->and($document['saved_to'])->toBe($this->path)
        ->and($document['calendars'])->toHaveCount(1)
        ->and($document['calendars'][0]['url'])->toBe(ElephStamp::FAKE_CALENDAR_URL)
        ->and($document['calendars'][0]['outcome'])->toBe('upgraded')
        ->and($document['calendars'][0]['block_height'])->toBe(700_001)
        ->and($document['calendars'][0]['commitment'])->toMatch('/^[0-9a-f]{64}$/');
});

it('emits a JSON list for several proofs, including errors', function (): void {
    $this->tester->run(['upgrade', 'receipts' => [$this->path, $this->dir . '/missing.ots'], '--json' => true]);

    $this->tester->assertCommandFailed();

    $documents = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($documents)->toHaveCount(2)
        ->and($documents[0]['status'])->toBe('pending')
        ->and($documents[0]['calendars'][0]['outcome'])->toBe('pending')
        ->and($documents[1]['error'])->toContain('missing.ots');
});

it('passes whitelist and timeout options to the client', function (): void {
    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--whitelist' => ['https://*.internal.example'], '--timeout' => '3']);

    expect($this->factory->lastOptions?->whitelist)->toBe(['https://*.internal.example'])
        ->and($this->factory->lastOptions?->useDefaultWhitelist)->toBeTrue()
        ->and($this->factory->lastOptions?->timeout)->toBe(3.0)
        ->and($this->factory->lastOptions?->resolvedWhitelist())->toBe([...ElephStamp::DEFAULT_UPGRADE_WHITELIST, 'https://*.internal.example']);
});

it('reports a verified answer and the source it was checked against', function (): void {
    $this->calendar->confirmAll(812_345);

    $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain('verified (6 confirmations)')
        ->toContain('checked against the blockchain through fake block source');

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--json' => true, '--all' => true]);
    $document = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($document['block_header_source'])->toBeNull()
        ->and($document['calendars'][0]['outcome'])->toBe('confirmed');
});

it('rejects an answer the blockchain does not back, and leaves the proof pending', function (): void {
    $this->calendar->confirmAll(812_345);
    $this->factory->blocks->reset();
    $this->factory->blocks->addBlock(812_345, str_repeat("\xee", 32));
    $before = file_get_contents($this->path);

    $status = $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    expect($status)->toBe(UpgradeCommand::STILL_PENDING)
        ->and($this->tester->getDisplay())->toContain('rejected')
        ->toContain('merkle root mismatch')
        ->toContain('does not hold up')
        ->and(file_get_contents($this->path))->toBe($before);

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--json' => true]);
    $document = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($document['status'])->toBe('pending')
        ->and($document['block_header_source'])->toBe('fake block source')
        ->and($document['calendars'][0]['outcome'])->toBe('rejected')
        ->and($document['calendars'][0]['verified'])->toBeFalse()
        ->and($document['calendars'][0]['block_height'])->toBeNull()
        ->and($document['calendars'][0]['claimed_block_height'])->toBe(812_345)
        ->and($document['calendars'][0]['confirmations'])->toBe(6)
        ->and($document['calendars'][0]['error'])->toContain('merkle root mismatch');
});

it('waits for a shallow block unless --min-confirmations allows it', function (): void {
    $this->calendar->confirmAll(812_345);
    $this->factory->blocks->setTipHeight(812_346);

    $status = $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    expect($status)->toBe(UpgradeCommand::STILL_PENDING)
        ->and($this->tester->getDisplay())->toContain('unconfirmed')
        ->toContain('2 of the 6 confirmations')
        ->toContain('not merged yet')
        ->toContain('too shallow');

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--min-confirmations' => '2']);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain('verified (2 confirmations)');
});

it('reports an answer it could not check and suggests --no-verify', function (): void {
    $this->calendar->confirmAll(812_345);
    $this->factory->blocks->reset();

    $status = $this->tester->run(['upgrade', 'receipts' => [$this->path]]);

    expect($status)->toBe(UpgradeCommand::STILL_PENDING)
        ->and($this->tester->getDisplay())->toContain('unverifiable')
        ->toContain('no block registered')
        ->toContain('--no-verify');

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--no-verify' => true]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain('Bitcoin block 812345 (not checked)')
        ->not->toContain('checked against the blockchain')
        ->and(Receipt::fromPath($this->path)->isComplete())->toBeTrue();
});

it('passes explorer choices to the client and validates --min-confirmations', function (): void {
    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--explorer' => ['blockstream'], '--explorer-url' => ['https://esplora.internal/api']]);

    expect($this->factory->lastOptions?->explorers)->toBe([CondorcetVote\ElephStamp\Verify\Explorer::Blockstream])
        ->and($this->factory->lastOptions?->explorerUrls)->toBe(['https://esplora.internal/api']);

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--explorer' => ['nope']]);
    $this->tester->assertCommandFailed();
    expect($this->tester->getDisplay())->toContain('Unknown explorer "nope"');

    $this->tester->run(['upgrade', 'receipts' => [$this->path], '--min-confirmations' => '0']);
    $this->tester->assertCommandFailed();
    expect($this->tester->getDisplay())->toContain('--min-confirmations must be at least 1');
});
