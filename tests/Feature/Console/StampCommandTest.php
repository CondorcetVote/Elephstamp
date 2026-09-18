<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Console\Application;
use CondorcetVote\ElephStamp\{ElephStamp, Receipt};
use Symfony\Component\Console\Tester\ApplicationTester;
use Tests\Feature\Console\FakeClientFactory;

function stampCli(?FakeClientFactory $factory = null): ApplicationTester
{
    $application = new Application($factory ?? new FakeClientFactory);
    $application->setAutoExit(false);

    return new ApplicationTester($application);
}

beforeEach(function (): void {
    $this->dir = makeTempDir();
    file_put_contents($this->dir . '/a.txt', 'file A');
    file_put_contents($this->dir . '/b.txt', 'file B');
});

it('stamps a file and writes the proof next to it', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt']]);

    $tester->assertCommandIsSuccessful();

    $receipt = Receipt::fromPath($this->dir . '/a.txt.ots');

    expect($receipt->isPending())->toBeTrue()
        ->and($receipt->fileDigestHex())->toBe(hash('sha256', 'file A'))
        ->and($tester->getDisplay())->toContain('a.txt.ots')
        ->toContain(ElephStamp::FAKE_CALENDAR_URL)
        ->toContain('1 proof written')
        ->toContain('elephstamp upgrade');
});

it('stamps several files in one submission, each with its own proof', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt', $this->dir . '/b.txt']]);

    $tester->assertCommandIsSuccessful();

    $a = Receipt::fromPath($this->dir . '/a.txt.ots');
    $b = Receipt::fromPath($this->dir . '/b.txt.ots');

    $commitment = static fn(Receipt $r): string => bin2hex($r->detachedTimestampFile()->timestamp->findPending()[0]['msg']);

    expect($a->fileDigestHex())->toBe(hash('sha256', 'file A'))
        ->and($b->fileDigestHex())->toBe(hash('sha256', 'file B'))
        ->and($commitment($a))->toBe($commitment($b))
        ->and($tester->getDisplay())->toContain('2 proofs written');
});

it('writes to --output for a single file', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt'], '--output' => $this->dir . '/elsewhere.ots']);

    $tester->assertCommandIsSuccessful();

    expect(is_file($this->dir . '/elsewhere.ots'))->toBeTrue()
        ->and(is_file($this->dir . '/a.txt.ots'))->toBeFalse();
});

it('refuses --output with several files', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt', $this->dir . '/b.txt'], '--output' => $this->dir . '/x.ots']);

    $tester->assertCommandFailed();

    expect($tester->getDisplay())->toContain('--output only makes sense with a single file')
        ->and(is_file($this->dir . '/a.txt.ots'))->toBeFalse();
});

it('stamps a raw digest', function (): void {
    $tester = stampCli();
    $digest = hash('sha256', 'elsewhere');

    $tester->run(['stamp', '--digest' => strtoupper($digest), '--output' => $this->dir . '/digest.ots']);

    $tester->assertCommandIsSuccessful();

    expect(Receipt::fromPath($this->dir . '/digest.ots')->fileDigestHex())->toBe($digest);
});

it('rejects a malformed digest', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', '--digest' => 'not-hex']);

    $tester->assertCommandFailed();

    expect($tester->getDisplay())->toContain('64-character hex SHA-256 digest');
});

it('refuses both files and a digest, and nothing at all', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt'], '--digest' => hash('sha256', 'x')]);
    $tester->assertCommandFailed();
    expect($tester->getDisplay())->toContain('either files or --digest');

    $tester->run(['stamp']);
    $tester->assertCommandFailed();
    expect($tester->getDisplay())->toContain('Nothing to stamp');
});

it('does not overwrite an existing proof unless forced', function (): void {
    $tester = stampCli();
    file_put_contents($this->dir . '/a.txt.ots', 'precious');

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt']]);

    $tester->assertCommandFailed();

    expect(unwrapped($tester->getDisplay()))->toContain(unwrapped('already exists. Pass --force'))
        ->and(file_get_contents($this->dir . '/a.txt.ots'))->toBe('precious');

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt'], '--force' => true]);

    $tester->assertCommandIsSuccessful();

    expect(Receipt::fromPath($this->dir . '/a.txt.ots')->isPending())->toBeTrue();
});

it('commits to the plain digest with --no-nonce and says so', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt'], '--no-nonce' => true]);

    $tester->assertCommandIsSuccessful();

    $pending = Receipt::fromPath($this->dir . '/a.txt.ots')->detachedTimestampFile()->timestamp->findPending();

    expect(bin2hex($pending[0]['msg']))->toBe(hash('sha256', 'file A'))
        ->and($tester->getDisplay())->toContain('without nonce');
});

it('passes calendar options through to the client', function (): void {
    $factory = new FakeClientFactory;
    $tester = stampCli($factory);

    $tester->run([
        'stamp',
        'files' => [$this->dir . '/a.txt'],
        '--calendar' => ['https://one.example', 'https://two.example'],
        '--required' => '1',
        '--timeout' => '2.5',
    ]);

    $tester->assertCommandIsSuccessful();

    expect($factory->lastOptions?->calendarUrls)->toBe(['https://one.example', 'https://two.example'])
        ->and($factory->lastOptions?->requiredCalendars)->toBe(1)
        ->and($factory->lastOptions?->timeout)->toBe(2.5);
});

it('reports an unreadable file as an error', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', 'files' => [$this->dir . '/missing.txt']]);

    $tester->assertCommandFailed();

    expect(unwrapped($tester->getDisplay()))->toContain(unwrapped('does not exist or is not readable'));
});

it('shows the full digest of each file', function (): void {
    $tester = stampCli();

    $tester->run(['stamp', 'files' => [$this->dir . '/a.txt', $this->dir . '/b.txt']]);

    expect($tester->getDisplay())->toContain(hash('sha256', 'file A'))
        ->toContain(hash('sha256', 'file B'));
});
