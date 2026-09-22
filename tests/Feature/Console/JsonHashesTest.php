<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Calendar\FakeCalendarClient;
use CondorcetVote\ElephStamp\Console\{Application, ClientOptions};
use CondorcetVote\ElephStamp\FileToStamp;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tests\Feature\Console\FakeClientFactory;

/**
 * JSON is a machine contract: every hash it carries is complete, whatever the
 * verbosity. Abbreviation belongs to the human rendering only.
 */
beforeEach(function (): void {
    $this->dir = makeTempDir();
    $this->calendar = new FakeCalendarClient;
    $this->factory = new FakeClientFactory($this->calendar);

    $application = new Application($this->factory);
    $application->setAutoExit(false);
    $this->tester = new ApplicationTester($application);

    file_put_contents($this->dir . '/doc.txt', 'the document');

    $client = $this->factory->create(new ClientOptions);
    $this->receipt = $client->stamp(FileToStamp::fromPath($this->dir . '/doc.txt'));
    $this->pendingPath = $this->dir . '/pending.ots';
    $this->receipt->saveToPath($this->pendingPath);

    $this->calendar->confirm($this->receipt, 800_000);
    $client->upgrade($this->receipt);
    $this->factory->blocks->anchor($this->receipt, new DateTimeImmutable('2024-06-01 12:00:00 UTC'));
    $this->path = $this->dir . '/doc.txt.ots';
    $this->receipt->saveToPath($this->path);
});

/**
 * Run a command with --json at every verbosity and assert the document is the
 * same each time, then hand the decoded document over for field assertions.
 *
 * @param array<string, mixed> $input
 * @param Closure():void|null $reset called before each run, for a command that changes what it reads
 *
 * @return array<string, mixed>
 */
function jsonAtEveryVerbosity(ApplicationTester $tester, array $input, ?Closure $reset = null): array
{
    $verbosities = [
        OutputInterface::VERBOSITY_NORMAL,
        OutputInterface::VERBOSITY_VERBOSE,
        OutputInterface::VERBOSITY_VERY_VERBOSE,
        OutputInterface::VERBOSITY_DEBUG,
    ];

    $documents = [];

    foreach ($verbosities as $verbosity) {
        $reset?->__invoke();
        $tester->run($input + ['--json' => true], ['verbosity' => $verbosity]);

        $display = $tester->getDisplay();

        expect($display)->not->toContain('…');

        $documents[$verbosity] = json_decode($display, true, flags: \JSON_THROW_ON_ERROR);
    }

    // Every verbosity that prints must print exactly the same document.
    expect(array_unique(array_map(static fn(array $d): string => json_encode($d, \JSON_THROW_ON_ERROR), $documents)))->toHaveCount(1);

    // Quiet is the odd one: it silences the output altogether, JSON included.
    $reset?->__invoke();
    $tester->run($input + ['--json' => true], ['verbosity' => OutputInterface::VERBOSITY_QUIET]);

    expect($tester->getDisplay())->toBe('');

    return $documents[OutputInterface::VERBOSITY_VERBOSE];
}

/**
 * Every hex string nested anywhere in a JSON document, whatever the key.
 *
 * @param array<array-key, mixed> $document
 *
 * @return list<string>
 */
function hexValues(array $document): array
{
    $found = [];

    array_walk_recursive($document, static function (mixed $value) use (&$found): void {
        if (\is_string($value) && preg_match('/^[0-9a-f]{8,}$/', $value) === 1) {
            $found[] = $value;
        }
    });

    return $found;
}

it('never abbreviates a hash in info --json', function (): void {
    $document = jsonAtEveryVerbosity($this->tester, ['info', 'receipts' => [$this->path]]);

    expect($document['file']['digest'])->toBe(bin2hex($this->receipt->fileDigest()))
        ->and($document['calendars'][0]['commitment'])->toHaveLength(\strlen($document['calendars'][0]['commitment']))
        ->and($document['bitcoin_attestations'][0]['merkle_root'])->toHaveLength(64)
        ->and(hexValues($document))->not->toBeEmpty();
});

it('never abbreviates a hash in upgrade --json', function (): void {
    $this->calendar->confirmAll(812_345);
    $pending = file_get_contents($this->pendingPath);

    // upgrade rewrites the proof it reads, so each run starts from the same
    // pending bytes; only then is the document expected to be identical.
    $document = jsonAtEveryVerbosity(
        $this->tester,
        ['upgrade', 'receipts' => [$this->pendingPath], '--all' => true],
        fn() => file_put_contents($this->pendingPath, $pending),
    );

    expect($document['calendars'][0]['commitment'])->toMatch('/^[0-9a-f]+$/')
        ->and(hexValues($document))->not->toBeEmpty();
});

it('never abbreviates a hash in verify --json', function (): void {
    $document = jsonAtEveryVerbosity($this->tester, ['verify', 'receipts' => [$this->path]]);

    $anchor = $document['bitcoin_attestations'][0];

    expect($document['file']['digest'])->toHaveLength(64)
        ->and($anchor['proof_merkle_root'])->toHaveLength(64)
        ->and($anchor['block_merkle_root'])->toBe($anchor['proof_merkle_root'])
        ->and($anchor['block_hash'])->toHaveLength(64)
        ->and(hexValues($document))->not->toBeEmpty();
});
