<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Console\Application;
use CondorcetVote\ElephStamp\Console\Command\VerifyCommand;
use CondorcetVote\ElephStamp\FileToStamp;
use CondorcetVote\ElephStamp\Verify\Explorer;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tests\Feature\Console\FakeClientFactory;

beforeEach(function (): void {
    $this->dir = makeTempDir();
    $this->factory = new FakeClientFactory;

    $application = new Application($this->factory);
    $application->setAutoExit(false);
    $this->tester = new ApplicationTester($application);

    file_put_contents($this->dir . '/doc.txt', 'the document');
    $client = $this->factory->create(new CondorcetVote\ElephStamp\Console\ClientOptions);
    $this->receipt = $client->stamp(FileToStamp::fromPath($this->dir . '/doc.txt'));
    $this->factory->calendar->confirm($this->receipt, 800_000);
    $client->upgrade($this->receipt);
    $this->path = $this->dir . '/doc.txt.ots';
    $this->receipt->saveToPath($this->path);
});

it('verifies a proof against the file next to it', function (): void {
    $this->factory->blocks->anchor($this->receipt, new DateTimeImmutable('2024-06-01 12:00:00 UTC'));

    $this->tester->run(['verify', 'receipts' => [$this->path]]);

    $this->tester->assertCommandIsSuccessful();

    $display = $this->tester->getDisplay();

    expect($display)->toContain('VERIFIED')
        ->and(unwrapped($display))->toContain(unwrapped('existed before 2024-06-01 12:00:00 UTC (Bitcoin block 800000)'))
        ->and($display)->toContain('digest matches the proof')
        ->toContain('fake block source')
        ->toContain('verified — merkle roots match')
        ->toContain('6 confirmations')
        ->and(strpos($display, 'VERIFIED'))->toBeLessThan((int) strpos($display, 'Original'));
});

it('fails loudly when the file does not match', function (): void {
    $this->factory->blocks->anchor($this->receipt);
    file_put_contents($this->dir . '/other.txt', 'another document');

    $this->tester->run(['verify', 'receipts' => [$this->path], '--file' => $this->dir . '/other.txt']);

    $this->tester->assertCommandFailed();

    expect($this->tester->getDisplay())->toContain('VERIFICATION FAILED')
        ->toContain('DIGEST MISMATCH')
        ->and(unwrapped($this->tester->getDisplay()))->toContain(unwrapped('is not the file this proof was made for'));
});

it('accepts a digest instead of the file', function (): void {
    $this->factory->blocks->anchor($this->receipt);

    $this->tester->run(['verify', 'receipts' => [$this->path], '--digest' => strtoupper(hash('sha256', 'the document'))]);

    $this->tester->assertCommandIsSuccessful();

    expect(unwrapped($this->tester->getDisplay()))->toContain(unwrapped('digest ' . hash('sha256', 'the document') . ' existed before'));

    $this->tester->run(['verify', 'receipts' => [$this->path], '--digest' => 'zz']);

    $this->tester->assertCommandFailed();
});

it('fails when the block does not commit to the proof', function (): void {
    $this->factory->blocks->reset();
    $this->factory->blocks->addBlock(800_000, str_repeat("\xee", 32));

    $this->tester->run(['verify', 'receipts' => [$this->path]]);

    $this->tester->assertCommandFailed();

    expect($this->tester->getDisplay())->toContain('VERIFICATION FAILED')
        ->toContain('MISMATCH — the block does not commit to this proof');
});

it('waits for confirmations and exits with the not-yet code', function (): void {
    $this->factory->blocks->anchor($this->receipt);
    $this->factory->blocks->setTipHeight(800_001);

    $status = $this->tester->run(['verify', 'receipts' => [$this->path]]);

    expect($status)->toBe(VerifyCommand::NOT_YET)
        ->and($this->tester->getDisplay())->toContain('AWAITING CONFIRMATIONS')
        ->toContain('awaiting confirmations (2 of 6)');

    $this->tester->run(['verify', 'receipts' => [$this->path], '--min-confirmations' => '2']);

    $this->tester->assertCommandIsSuccessful();
});

it('reports a pending proof and an unreachable source as not verifiable yet', function (): void {
    $pending = $this->dir . '/pending.ots';
    $this->factory->create(new CondorcetVote\ElephStamp\Console\ClientOptions)->stamp(FileToStamp::fromContent('x'))->saveToPath($pending);

    expect($this->tester->run(['verify', 'receipts' => [$pending]]))->toBe(VerifyCommand::NOT_YET)
        ->and($this->tester->getDisplay())->toContain('PENDING')
        ->toContain('run "upgrade" first');

    // Complete proof, but the source knows no block at all.
    $this->factory->blocks->reset();

    expect($this->tester->run(['verify', 'receipts' => [$this->path]]))->toBe(VerifyCommand::NOT_YET)
        ->and($this->tester->getDisplay())->toContain('INCONCLUSIVE')
        ->toContain('unavailable');
});

it('explains when no original file is around', function (): void {
    $this->factory->blocks->anchor($this->receipt);
    unlink($this->dir . '/doc.txt');

    $this->tester->run(['verify', 'receipts' => [$this->path]]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain('The stamped data existed before')
        ->toContain('No original file was checked')
        ->toContain('not checked: none found next to the proof');
});

it('emits JSON', function (): void {
    $this->factory->blocks->anchor($this->receipt, new DateTimeImmutable('2024-06-01 12:00:00 UTC'));

    $this->tester->run(['verify', 'receipts' => [$this->path], '--json' => true]);

    $this->tester->assertCommandIsSuccessful();

    $document = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($document['verdict'])->toBe('verified')
        ->and($document['attested_at'])->toBe('2024-06-01T12:00:00+00:00')
        ->and($document['attesting_block_height'])->toBe(800_000)
        ->and($document['file']['subject'])->toBe($this->dir . '/doc.txt')
        ->and($document['file']['digest_matches'])->toBeTrue()
        ->and($document['source'])->toBe('fake block source')
        ->and($document['required_confirmations'])->toBe(6)
        ->and($document['bitcoin_attestations'][0]['outcome'])->toBe('verified')
        ->and($document['bitcoin_attestations'][0]['confirmations'])->toBe(6)
        ->and($document['bitcoin_attestations'][0]['block_merkle_root'])->toBe($document['bitcoin_attestations'][0]['proof_merkle_root'])
        ->and($document['bitcoin_attestations'][0]['transaction_id'])->toBeNull();

    $this->factory->blocks->setTipHeight(800_000);
    $this->tester->run(['verify', 'receipts' => [$this->path, $this->dir . '/missing.ots'], '--json' => true]);

    $documents = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($documents[0]['verdict'])->toBe('awaiting_confirmations')
        ->and($documents[0]['bitcoin_attestations'][0]['outcome'])->toBe('awaiting_confirmations')
        ->and($documents[1]['error'])->toContain('missing.ots');
});

it('passes explorer choices to the client and rejects unknown ones', function (): void {
    $this->factory->blocks->anchor($this->receipt);

    $this->tester->run(['verify', 'receipts' => [$this->path], '--explorer' => ['blockstream', 'mempool'], '--explorer-url' => ['https://esplora.internal/api'], '--timeout' => '4']);

    expect($this->factory->lastOptions?->explorers)->toBe([Explorer::Blockstream, Explorer::MempoolSpace])
        ->and($this->factory->lastOptions?->explorerUrls)->toBe(['https://esplora.internal/api'])
        ->and($this->factory->lastOptions?->timeout)->toBe(4.0)
        ->and($this->factory->lastOptions?->resolvedExplorers())->toBe([Explorer::Blockstream, Explorer::MempoolSpace]);

    $this->tester->run(['verify', 'receipts' => [$this->path], '--explorer' => ['nope']]);

    $this->tester->assertCommandFailed();

    expect($this->tester->getDisplay())->toContain('Unknown explorer "nope"');
});

it('refuses inconsistent options', function (): void {
    $this->tester->run(['verify', 'receipts' => [$this->path, $this->path], '--file' => $this->dir . '/doc.txt']);
    $this->tester->assertCommandFailed();
    expect($this->tester->getDisplay())->toContain('single proof');

    $this->tester->run(['verify', 'receipts' => [$this->path], '--file' => $this->dir . '/doc.txt', '--digest' => hash('sha256', 'x')]);
    $this->tester->assertCommandFailed();

    $this->tester->run(['verify', 'receipts' => [$this->path], '--min-confirmations' => '0']);
    $this->tester->assertCommandFailed();
});

it('stays verified but warns when another attestation does not match its block', function (): void {
    // Graft a second attestation naming a block that holds something else.
    $node = $this->receipt->detachedTimestampFile()->timestamp->findPending()[0]['node'];
    $branch = new CondorcetVote\ElephStamp\Timestamp($node->msg);
    $branch->addOp(new CondorcetVote\ElephStamp\Operation\Sha256)->addAttestation(new CondorcetVote\ElephStamp\Attestation\BitcoinAttestation(800_001));
    $node->merge($branch);
    $this->receipt->saveToPath($this->path);

    $this->factory->blocks->addBlock(800_001, str_repeat("\xee", 32));
    $this->factory->blocks->setTipHeight(800_010);

    $this->tester->run(['verify', 'receipts' => [$this->path]]);

    $this->tester->assertCommandIsSuccessful();

    $display = unwrapped($this->tester->getDisplay());

    expect($display)->toContain('VERIFIED')
        ->toContain(unwrapped('Warning: the attestation for block 800001 does not commit to this proof and was ignored'))
        ->toContain('MISMATCH');

    $this->tester->run(['verify', 'receipts' => [$this->path], '--json' => true]);
    $document = json_decode($this->tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);

    expect($document['verdict'])->toBe('verified')
        ->and(array_column($document['bitcoin_attestations'], 'outcome'))->toBe(['verified', 'merkle_root_mismatch']);
});

it('passes node settings to the client and requires --node for its companions', function (): void {
    $this->factory->blocks->anchor($this->receipt);

    $this->tester->run(['verify', 'receipts' => [$this->path], '--node' => 'http://127.0.0.1:8332', '--node-cookie' => '/var/lib/bitcoind/.cookie']);

    $this->tester->assertCommandIsSuccessful();

    expect($this->factory->lastOptions?->node)->toBe('http://127.0.0.1:8332')
        ->and($this->factory->lastOptions?->nodeCookieFile)->toBe('/var/lib/bitcoind/.cookie')
        ->and($this->factory->lastOptions?->nodeUser)->toBeNull()
        ->and($this->factory->lastOptions?->resolvedExplorers())->toBe([]);

    $this->tester->run(['verify', 'receipts' => [$this->path], '--node' => 'http://127.0.0.1:8332', '--node-user' => 'rpc', '--node-password' => 'secret', '--explorer' => ['blockstream']]);

    expect($this->factory->lastOptions?->nodeUser)->toBe('rpc')
        ->and($this->factory->lastOptions?->nodePassword)->toBe('secret')
        ->and($this->factory->lastOptions?->resolvedExplorers())->toBe([Explorer::Blockstream]);

    $this->tester->run(['verify', 'receipts' => [$this->path], '--node-user' => 'rpc']);

    $this->tester->assertCommandFailed();

    expect($this->tester->getDisplay())->toContain('--node-user, --node-password and --node-cookie need --node');
});
