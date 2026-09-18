<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Console\Application;
use CondorcetVote\ElephStamp\Receipt;
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

it('reports an unreadable proof and keeps going', function (): void {
    $this->tester->run(['tree', 'receipts' => [__DIR__ . '/../../fixtures/missing.ots', $this->fixture]]);

    $this->tester->assertCommandFailed();

    expect(unwrapped($this->tester->getDisplay()))->toContain('missing.ots')
        ->and($this->tester->getDisplay())->toContain('pending attestation');
});
