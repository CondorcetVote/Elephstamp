<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Command;

use CondorcetVote\ElephStamp\Console\{ClientFactory, ClientOptions, Formatter};
use CondorcetVote\ElephStamp\Exception\{ElephStampException, InvalidInputException};
use CondorcetVote\ElephStamp\{FileToStamp, Receipt};
use Symfony\Component\Console\Attribute\{Argument, AsCommand, Option};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'stamp',
    description: 'Create a timestamp proof (.ots) for one or more files',
    help: <<<'HELP'
        Hashes each file, submits the commitment to the calendar servers and writes
        one <comment>.ots</comment> proof per file (next to it, as <comment><file>.ots</comment>). When several files
        are given they share a single calendar submission, but each still gets its own
        independent proof.

        A fresh proof is <comment>pending</comment>: the calendars have recorded it, Bitcoin has not
        confirmed it yet. Come back in a few hours and run <info>upgrade</info> on the proofs.

        By default a random nonce is mixed into the digest so the calendars never learn
        the real file hash. Pass <comment>--no-nonce</comment> to commit to the plain hash instead.

        Exit codes: <info>0</info> all proofs written, <info>1</info> nothing written (bad usage, unreadable file or too few calendars).
        HELP,
    usages: [
        'contract.pdf',
        'a.pdf b.pdf c.pdf',
        'contract.pdf --output proofs/contract.ots',
        '--digest 03ba204e50d126e4674c005e04d82e84c21366780af1f43bd54a37816b6ab340 -o hello.ots',
        'contract.pdf -c https://ots.internal.example -m 1',
    ],
)]
final class StampCommand
{
    public function __construct(private readonly ClientFactory $clientFactory) {}

    /**
     * @param list<string> $files
     * @param list<string> $calendar
     */
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        #[Argument(description: 'File(s) to timestamp; several files share one calendar submission')]
        array $files = [],
        #[Option(description: 'Where to write the proof (single file or digest only); defaults to <file>.ots', name: 'output', shortcut: 'o')]
        ?string $outputPath = null,
        #[Option(description: 'Timestamp a hex-encoded SHA-256 digest instead of a file', name: 'digest')]
        ?string $digest = null,
        #[Option(description: 'Commit to the plain file hash; the calendars (and sibling proofs) then learn it', name: 'no-nonce')]
        bool $noNonce = false,
        #[Option(description: 'Calendar URL to submit to (repeatable); replaces the default list', shortcut: 'c')]
        array $calendar = [],
        #[Option(description: 'How many calendars must accept the stamp (the "m" of m-of-n); default 2, or 1 with a single calendar', shortcut: 'm')]
        ?int $required = null,
        #[Option(description: 'Seconds to wait for a calendar before giving up on it')]
        ?float $timeout = null,
        #[Option(description: 'Overwrite an existing .ots file', shortcut: 'f')]
        bool $force = false,
    ): int {
        if ($files === [] && $digest === null) {
            $io->error('Nothing to stamp: give at least one file, or a --digest.');

            return Command::FAILURE;
        }

        if ($files !== [] && $digest !== null) {
            $io->error('Give either files or --digest, not both.');

            return Command::FAILURE;
        }

        if ($outputPath !== null && \count($files) > 1) {
            $io->error('--output only makes sense with a single file: several files each get their own <file>.ots.');

            return Command::FAILURE;
        }

        try {
            [$labels, $targets, $inputs] = $digest !== null
                ? self::fromDigest($digest, $outputPath)
                : self::fromFiles($files, $outputPath);

            foreach ($targets as $target) {
                if (is_file($target) && !$force) {
                    $io->error(\sprintf('%s already exists. Pass --force to overwrite it.', $target));

                    return Command::FAILURE;
                }
            }

            if ($noNonce) {
                $inputs = array_map(static fn(FileToStamp $input): FileToStamp => $input->withoutNonce(), $inputs);
            }

            $client = $this->clientFactory->create(new ClientOptions(
                calendarUrls: $calendar,
                requiredCalendars: $required,
                timeout: $timeout,
            ));

            $io->text(\sprintf('Submitting %s to the calendars…', Formatter::plural(\count($inputs), 'commitment')));

            $receipts = $client->stampMany(...$inputs);

            foreach ($receipts as $index => $receipt) {
                $receipt->saveToPath($targets[$index]);
            }
        } catch (ElephStampException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $this->report($io, $labels, $targets, $receipts, $noNonce);

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $files
     *
     * @return array{list<string>, list<string>, list<FileToStamp>}
     */
    private static function fromFiles(array $files, ?string $outputPath): array
    {
        $inputs = [];
        $targets = [];

        foreach ($files as $file) {
            $inputs[] = FileToStamp::fromPath($file);
            $targets[] = $outputPath ?? $file . '.ots';
        }

        return [$files, $targets, $inputs];
    }

    /**
     * @return array{list<string>, list<string>, list<FileToStamp>}
     */
    private static function fromDigest(string $digest, ?string $outputPath): array
    {
        $hex = strtolower($digest);

        if (!ctype_xdigit($hex) || \strlen($hex) !== 64) {
            throw new InvalidInputException('--digest must be a 64-character hex SHA-256 digest');
        }

        $raw = hex2bin($hex);

        if ($raw === false) {
            throw new InvalidInputException('--digest must be a 64-character hex SHA-256 digest');
        }

        return [['digest ' . Formatter::abbreviate($hex)], [$outputPath ?? $hex . '.ots'], [FileToStamp::fromDigest($raw)]];
    }

    /**
     * @param list<string>  $labels
     * @param list<string>  $targets
     * @param list<Receipt> $receipts
     */
    private function report(SymfonyStyle $io, array $labels, array $targets, array $receipts, bool $noNonce): void
    {
        $rows = [];

        foreach ($receipts as $index => $receipt) {
            $rows[] = [
                $labels[$index],
                $receipt->fileDigestHex(),
                $targets[$index],
            ];
        }

        $io->table(['File', \sprintf('%s digest', $receipts[0]->hashOperation()->describe()), 'Proof written to'], $rows);

        $calendars = $receipts[0]->pendingCalendarUris();
        $io->text(\sprintf('Accepted by %s:', Formatter::plural(\count($calendars), 'calendar')));
        $io->listing($calendars);

        $io->success(\sprintf(
            '%s written. Status: pending — Bitcoin confirmation usually takes a few hours.',
            Formatter::plural(\count($receipts), 'proof'),
        ));

        if ($noNonce) {
            $io->note('Stamped without nonce: the calendars now know the plain file digest.');
        }

        $io->text(\sprintf('Later, run <info>elephstamp upgrade %s</info> to collect the completed proof%s.', implode(' ', array_map(escapeshellarg(...), $targets)), \count($targets) > 1 ? 's' : ''));
        $io->newLine();
    }
}
