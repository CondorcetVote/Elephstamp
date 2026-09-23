<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\PendingAttestation;
use CondorcetVote\ElephStamp\Console\Application;
use CondorcetVote\ElephStamp\Operation\{Append, Sha256};
use CondorcetVote\ElephStamp\{DetachedTimestampFile, Receipt, Timestamp};
use Symfony\Component\Console\Tester\ApplicationTester;

beforeEach(function (): void {
    $application = new Application;
    $application->setAutoExit(false);
    $this->tester = new ApplicationTester($application);
    $this->fixture = __DIR__ . '/../../fixtures/known-and-unknown-notary.txt.ots';
});

it('draws every operation with the hash it produces', function (): void {
    $this->tester->run(['tree', 'receipts' => [$this->fixture]]);

    $this->tester->assertCommandIsSuccessful();

    $display = $this->tester->getDisplay();
    $digest = 'd288b2ee212b01e3e5f6d333df3a4d53f292cc3f07b09013c0b40c8e7dcb9c03';

    expect($display)
        ->toContain('file sha256 digest ' . $digest)
        // append nonce = digest . nonce, computed and shown in full
        ->toContain('append 46d842bd5d8377e0f42041bec9bda667 = ' . $digest . '46d842bd5d8377e0f42041bec9bda667')
        ->toContain('sha256 = 95e2b314af1a11524778ade82197444350d46c60894e7839863a401746e5c00f')
        ->toContain('├── ')
        ->toContain('└── ')
        ->toContain('pending attestation → https://bob.btc.calendar.opentimestamps.org')
        ->toContain('unknown attestation (tag 0102030405060708)');
});

it('hides the hashes on request', function (): void {
    $this->tester->run(['tree', 'receipts' => [$this->fixture], '--no-hashes' => true]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('append 46d842bd5d8377e0f42041bec9bda667')
        ->not->toContain(' = ');
});

it('prints the reference layout with --plain', function (): void {
    $this->tester->run(['tree', 'receipts' => [$this->fixture], '--plain' => true]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())->toContain(Receipt::fromPath($this->fixture)->describe());
});

it('shows a bitcoin attestation leaf on a complete proof', function (): void {
    $this->tester->run(['tree', 'receipts' => [__DIR__ . '/../../fixtures/hello-world.txt.ots']]);

    expect($this->tester->getDisplay())->toContain('bitcoin attestation → block 358391');
});

it('groups the calendar branches under the digest submitted to them', function (): void {
    $this->tester->run(['tree', 'receipts' => [__DIR__ . '/../../fixtures/two-calendars.txt.ots']]);

    $this->tester->assertCommandIsSuccessful();

    expect($this->tester->getDisplay())
        ->toContain('├── append 839037eef449dec6dac322ca97347c45 = efaa174f68e59705757460f4f7d204bd2b535cfd194d9d945418732129404ddb839037eef449dec6dac322ca97347c45 (privacy nonce)')
        ->toContain('└── submitted to the calendars = 679a59f6661f9d809d6f72d2cc080a20435c5c793ace1961ca78e38693f2f53d' . \PHP_EOL)
        ->toContain('   ├── append 6b4023b6edd3a0eeeb09e5d718723b9e')
        ->toContain('   └── append a3ad701ef9f10535a84968b5a99d8580');
});

it('submits the merkle root of a batch', function (): void {
    $this->tester->run(['tree', 'receipts' => [__DIR__ . '/../../fixtures/merkle2.txt.ots'], '--no-hashes' => true]);

    expect($this->tester->getDisplay())
        ->toContain('├── append b63d8f213d047298b8ab4595acd8e5d0 (privacy nonce)')
        ->toContain('├── append 026356e7972f023930ec84c213adedc4050460973935bbd2f4df3d7bd5dec55f' . \PHP_EOL)
        ->toContain('└── submitted to the calendars' . \PHP_EOL);
});

it('says when the file digest itself was submitted', function (): void {
    $root = new Timestamp(hash('sha256', 'file A', true));
    $root->addOp(new Append(str_repeat("\x01", 16)))->addOp(new Sha256)->addAttestation(new PendingAttestation('https://a.example'));
    $root->addOp(new Append(str_repeat("\x02", 16)))->addOp(new Sha256)->addAttestation(new PendingAttestation('https://b.example'));
    $path = makeTempDir() . '/a.txt.ots';
    new Receipt(new DetachedTimestampFile(new Sha256, $root))->saveToPath($path);

    $this->tester->run(['tree', 'receipts' => [$path]]);

    expect($this->tester->getDisplay())
        ->toContain('└── submitted to the calendars = ' . hash('sha256', 'file A') . ' (the file digest itself, no nonce)')
        ->not->toContain('privacy nonce)');
});

it('marks no submission point on a single calendar branch', function (): void {
    $this->tester->run(['tree', 'receipts' => [__DIR__ . '/../../fixtures/incomplete.txt.ots']]);

    expect($this->tester->getDisplay())
        ->not->toContain('submitted to the calendars')
        ->not->toContain('privacy nonce');
});

it('reports an unreadable proof and keeps going', function (): void {
    $this->tester->run(['tree', 'receipts' => [__DIR__ . '/../../fixtures/missing.ots', $this->fixture]]);

    $this->tester->assertCommandFailed();

    expect(unwrapped($this->tester->getDisplay()))->toContain('missing.ots')
        ->and($this->tester->getDisplay())->toContain('pending attestation');
});
