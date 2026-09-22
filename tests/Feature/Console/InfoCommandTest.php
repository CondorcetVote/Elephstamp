<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

const FIXTURES = __DIR__ . '/../../fixtures';

beforeEach(function (): void {
    $application = new Application;
    $application->setAutoExit(false);
    $this->tester = new ApplicationTester($application);
});

it('explains a complete proof, with the transaction id and merkle root explorers show', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/hello-world.txt.ots']]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('complete')
        ->toContain('Bitcoin block 358391')
        ->toContain('sha256')
        ->toContain('03ba204e50d126e4674c005e04d82e84c21366780af1f43bd54a37816b6ab340')
        ->toContain('hello-world.txt')
        ->toContain('digest matches')
        ->toContain('688 bytes')
        ->toContain('Calendar submissions (0)')
        ->toContain('Bitcoin attestations (1)')
        ->toContain('8a1b66ecb7cbd07d8139a7e7d7f2c41aab1f5009b8364aaf61d03ad245e47e00')
        ->toContain('Transaction id   7e9f0f7d9daa2d9e51b2e22f4abe814c3f90539afa778a9bef88dc64627cb2ec')
        ->toContain('https://mempool.space/tx/7e9f0f7d9daa2d9e51b2e22f4abe814c3f90539afa778a9bef88dc64627cb2ec')
        ->toContain('not verified');
});

it('lists every calendar submission of a pending proof with its recorded time', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/two-calendars.txt.ots']]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('pending')
        ->toContain('waiting on 2 calendars')
        ->toContain('https://alice.btc.calendar.opentimestamps.org')
        ->toContain('https://bob.btc.calendar.opentimestamps.org')
        ->toContain('2016-09-10')
        ->toContain('pending, upgradable')
        ->toContain('Bitcoin attestations (0)')
        ->toContain('not checked: two-calendars.txt not found next to the proof');
});

it('shows an unsupported attestation without choking on it', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/known-and-unknown-notary.txt.ots']]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('Unsupported attestations (1)')
        ->toContain('tag 0102030405060708')
        ->toContain('Calendar submissions (1)');
});

it('flags a digest mismatch against --file and fails', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/hello-world.txt.ots'], '--file' => FIXTURES . '/incomplete.txt']);

    $this->tester->assertCommandFailed();

    $display = $this->tester->getDisplay();

    // The banner comes first, before any other detail of the report. The
    // sentence carries a long path, so compare without line wrapping.
    expect($display)->toContain('DIGEST MISMATCH')
        ->and(unwrapped($display))->toContain(unwrapped('is not the file this proof was made for'))
        ->and(strpos($display, 'DIGEST MISMATCH'))->toBeLessThan((int) strpos($display, 'Status'));
});

it('accepts --file pointing at the right file', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/merkle1.txt.ots'], '--file' => FIXTURES . '/merkle1.txt']);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain('digest matches');
});

it('refuses --file with several proofs', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/merkle1.txt.ots', FIXTURES . '/merkle2.txt.ots'], '--file' => FIXTURES . '/merkle1.txt']);

    $this->tester->assertCommandFailed();

    expect($this->tester->getDisplay())->toContain('--file only makes sense with a single proof');
});

it('marks calendars off the whitelist as not upgradable', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/incomplete.txt.ots'], '--no-default-whitelist' => true]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('none of them upgradable')
        ->toContain('not upgradable')
        ->toContain('not on the whitelist');

    $this->tester->run(['info', 'receipts' => [FIXTURES . '/incomplete.txt.ots'], '--no-default-whitelist' => true, '--whitelist' => ['https://*.calendar.opentimestamps.org']]);

    expect($this->tester->getDisplay())->toContain('pending, upgradable');
});

it('rejects a non-https whitelist pattern', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/incomplete.txt.ots'], '--whitelist' => ['http://plain.example']]);

    $this->tester->assertCommandFailed();

    expect($this->tester->getDisplay())->toContain('must use https');
});

it('reports an unreadable proof and keeps going', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/missing.ots', FIXTURES . '/incomplete.txt.ots']]);

    $this->tester->assertCommandFailed();

    // The error block carries two long paths, so compare without line wrapping.
    expect(unwrapped($this->tester->getDisplay()))->toContain('missing.ots')
        ->toContain(unwrapped('does not exist or is not readable'))
        ->and($this->tester->getDisplay())->toContain('https://alice.btc.calendar.opentimestamps.org');
});

it('abbreviates commitments unless verbose', function (): void {
    $full = '57d982df8b35bc0a91a93d6d17e0162868dc123bc8b1bbfab22af9268f7fea70376b5cb0b1f26e2e55590477';

    $this->tester->run(['info', 'receipts' => [FIXTURES . '/merkle1.txt.ots']]);

    expect($this->tester->getDisplay())
        ->toContain('57d982df…55590477')
        ->not->toContain($full)
        ->toContain('Commitments are abbreviated');

    $this->tester->run(['info', 'receipts' => [FIXTURES . '/merkle1.txt.ots']], ['verbosity' => 64]);

    expect($this->tester->getDisplay())
        ->toContain($full)
        ->not->toContain('abbreviated');
});

it('emits JSON for one proof', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/known-and-unknown-notary.txt.ots'], '--json' => true]);

    $this->tester->assertCommandIsSuccessful();

    $document = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($document['status'])->toBe('pending')
        ->and($document['bitcoin_block_height'])->toBeNull()
        ->and($document['file']['hash'])->toBe('sha256')
        ->and($document['file']['digest'])->toBe('d288b2ee212b01e3e5f6d333df3a4d53f292cc3f07b09013c0b40c8e7dcb9c03')
        ->and($document['file']['path'])->toBeNull()
        ->and($document['file']['digest_matches'])->toBeNull()
        ->and($document['proof_size_bytes'])->toBe(265)
        ->and($document['calendars'])->toHaveCount(1)
        ->and($document['calendars'][0]['url'])->toBe('https://bob.btc.calendar.opentimestamps.org')
        ->and($document['calendars'][0]['recorded_at'])->toBe('2016-09-26T04:08:24+00:00')
        ->and($document['calendars'][0]['confirmed'])->toBeFalse()
        ->and($document['calendars'][0]['upgradable'])->toBeTrue()
        ->and($document['bitcoin_attestations'])->toBe([])
        ->and($document['unknown_attestations'])->toBe([['tag' => '0102030405060708', 'payload_bytes' => 46]]);
});

it('emits a JSON list for several proofs, including errors', function (): void {
    $this->tester->run(['info', 'receipts' => [FIXTURES . '/hello-world.txt.ots', FIXTURES . '/missing.ots'], '--json' => true]);

    $this->tester->assertCommandFailed();

    $documents = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($documents)->toHaveCount(2)
        ->and($documents[0]['status'])->toBe('complete')
        ->and($documents[0]['bitcoin_block_height'])->toBe(358_391)
        ->and($documents[0]['file']['digest_matches'])->toBeTrue()
        ->and($documents[0]['bitcoin_attestations'][0]['merkle_root'])->toBe('8a1b66ecb7cbd07d8139a7e7d7f2c41aab1f5009b8364aaf61d03ad245e47e00')
        ->and($documents[0]['bitcoin_attestations'][0]['transaction_id'])->toBe('7e9f0f7d9daa2d9e51b2e22f4abe814c3f90539afa778a9bef88dc64627cb2ec')
        ->and($documents[1]['error'])->toContain('missing.ots');
});

it('says when a Bitcoin attestation embeds no recognisable transaction', function (): void {
    $client = CondorcetVote\ElephStamp\ElephStamp::fake();
    $receipt = $client->stamp(CondorcetVote\ElephStamp\FileToStamp::fromContent('no tx'));
    $client->fakeCalendar()->confirmAll(700_000);
    $client->upgrade($receipt);
    $path = makeTempPath();
    $receipt->saveToPath($path);

    $this->tester->run(['info', 'receipts' => [$path]]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('Block height     700000')
        ->toContain('unknown: the proof does not embed a recognisable transaction')
        ->toContain('https://mempool.space/block/700000');

    $this->tester->run(['info', 'receipts' => [$path], '--json' => true]);

    $document = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($document['bitcoin_attestations'][0]['transaction_id'])->toBeNull()
        ->and($document['bitcoin_attestations'][0]['block_height'])->toBe(700_000);
});

it('stops calling the other calendars upgradable once the proof is complete', function (): void {
    // Two calendars, only one confirms: the proof is complete, the other
    // submission stays pending but will never be polled again.
    $calendar = new CondorcetVote\ElephStamp\Calendar\FakeCalendarClient;
    $client = new CondorcetVote\ElephStamp\ElephStamp(
        calendarClient: $calendar,
        calendarUrls: ['https://one.calendar.opentimestamps.org', 'https://two.calendar.opentimestamps.org'],
        randomSource: new CondorcetVote\ElephStamp\Random\DeterministicRandomSource,
        blockHeaderSource: $calendar->blocks(),
    );
    $receipt = $client->stamp(CondorcetVote\ElephStamp\FileToStamp::fromContent('one of two'));
    $calendar->confirm($receipt, 700_000);
    $client->upgrade($receipt);
    $path = makeTempPath();
    $receipt->saveToPath($path);

    $this->tester->run(['info', 'receipts' => [$path]]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('complete')
        ->toContain('confirmed in block 700000')
        ->not->toContain('pending, upgradable');
});
