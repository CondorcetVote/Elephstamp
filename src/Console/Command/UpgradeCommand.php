<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Command;

use CondorcetVote\ElephStamp\Console\{ClientFactory, ClientOptions, Formatter};
use CondorcetVote\ElephStamp\Exception\ElephStampException;
use CondorcetVote\ElephStamp\Upgrade\{CalendarUpgradeResult, UpgradeOutcome, UpgradeReport};
use CondorcetVote\ElephStamp\Verify\Verifier;
use CondorcetVote\ElephStamp\{Receipt, Status};
use Symfony\Component\Console\Attribute\{Argument, AsCommand, Option};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'upgrade',
    description: 'Ask the calendars whether pending proofs have been confirmed, and save the completed ones',
    help: <<<'HELP'
        For each <comment>.ots</comment> proof, polls every calendar it is pending on and reports what
        each one answered: <comment>upgraded</comment>, <comment>still pending</comment>, <comment>failed</comment>, <comment>rejected</comment>
        (the calendar returned a proof for another digest, or for a block that does not
        commit to it), <comment>unconfirmed</comment> (its block is still too shallow), <comment>unverifiable</comment>
        (the explorer could not answer) or <comment>skipped</comment> (its host is not on the whitelist,
        so it was never contacted).

        A calendar's answer is untrusted, so every Bitcoin attestation it returns is
        checked against the blockchain before it is merged, exactly as <comment>verify</comment> does:
        the block explorer (mempool.space by default, see <comment>--explorer</comment>) is asked for the
        block's header, the merkle roots must match, and the block must be buried under
        <comment>--min-confirmations</comment> blocks. An answer that fails the check is not merged and the
        calendar stays pending. Pass <comment>--no-verify</comment> to merge whatever the calendars return.

        A proof that gained new attestations is written back in place (or to
        <comment>--output</comment> for a single proof). Use <comment>--dry-run</comment> to poll without writing.

        One Bitcoin attestation makes a proof complete, so a complete proof is normally
        left alone. Pass <comment>--all</comment> to keep polling the calendars it is still pending
        on and collect every attestation.

        Only calendars matching the whitelist are contacted: a proof is untrusted input
        and must not be able to send requests to arbitrary hosts. Add private
        calendars with <comment>--whitelist</comment>.

        Exit codes: <info>0</info> every proof is complete, <info>2</info> at least one is still pending,
        <info>1</info> bad usage, or a proof could not be read or written.
        HELP,
    usages: [
        'contract.pdf.ots',
        'proofs/*.ots',
        'contract.pdf.ots --dry-run',
        'contract.pdf.ots --all',
        'contract.pdf.ots --explorer blockstream --min-confirmations 3',
        'contract.pdf.ots --no-verify',
        'contract.pdf.ots -l https://*.internal.example --timeout 5',
    ],
)]
final class UpgradeCommand
{
    /**
     * Exit code when everything worked but at least one proof is still pending.
     */
    public const int STILL_PENDING = 2;

    public function __construct(private readonly ClientFactory $clientFactory) {}

    /**
     * @param list<string> $receipts
     * @param list<string> $whitelist
     * @param list<string> $explorer
     * @param list<string> $explorerUrl
     */
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        #[Argument(description: 'Proof file(s) (.ots) to upgrade')]
        array $receipts,
        #[Option(description: 'Poll the calendars but do not write anything', name: 'dry-run')]
        bool $dryRun = false,
        #[Option(description: 'Also poll the calendars still pending in an already complete proof, to collect every attestation', name: 'all')]
        bool $all = false,
        #[Option(description: 'Write the upgraded proof here instead of in place (single proof only)', name: 'output', shortcut: 'o')]
        ?string $outputPath = null,
        #[Option(description: 'Extra host pattern the upgrade may contact (repeatable), e.g. https://*.internal.example', shortcut: 'l')]
        array $whitelist = [],
        #[Option(description: 'Drop the built-in whitelist of public calendars; only --whitelist patterns remain', name: 'no-default-whitelist')]
        bool $noDefaultWhitelist = false,
        #[Option(description: 'Merge whatever the calendars answer without checking it against the blockchain first', name: 'no-verify')]
        bool $noVerify = false,
        #[Option(description: 'Blocks a block named by a calendar must be buried under before its attestation is merged, itself included', name: 'min-confirmations')]
        int $minConfirmations = Verifier::DEFAULT_REQUIRED_CONFIRMATIONS,
        #[Option(description: 'Block explorer to check the answers against: mempool (default) or blockstream. Repeat to require several to agree', shortcut: 'e', suggestedValues: ['mempool', 'blockstream'])]
        array $explorer = [],
        #[Option(description: 'Base URL of another Esplora-compatible explorer, e.g. a self-hosted one (repeatable, https only)', name: 'explorer-url')]
        array $explorerUrl = [],
        #[Option(description: 'Seconds to wait for a calendar or explorer before giving up on it')]
        ?float $timeout = null,
        #[Option(description: 'Print machine-readable JSON instead of the report')]
        bool $json = false,
    ): int {
        if ($outputPath !== null && \count($receipts) > 1) {
            $io->error('--output only makes sense with a single proof.');

            return Command::FAILURE;
        }

        if ($minConfirmations < 1) {
            $io->error('--min-confirmations must be at least 1.');

            return Command::FAILURE;
        }

        try {
            $client = $this->clientFactory->create(new ClientOptions(
                whitelist: $whitelist,
                useDefaultWhitelist: !$noDefaultWhitelist,
                timeout: $timeout,
                explorers: ClientOptions::explorersFromNames($explorer),
                explorerUrls: $explorerUrl,
            ));
        } catch (ElephStampException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $failed = 0;
        $pending = 0;
        $documents = [];

        foreach ($receipts as $path) {
            $target = $outputPath ?? $path;

            try {
                $receipt = Receipt::fromPath($path);
                $wasComplete = $receipt->isComplete();
                $report = $client->upgradeWithReport($receipt, pollAll: $all, verify: !$noVerify, requiredConfirmations: $minConfirmations);
                $saved = false;

                if ($report->changed() && !$dryRun) {
                    $receipt->saveToPath($target);
                    $saved = true;
                }
            } catch (ElephStampException $exception) {
                ++$failed;

                if ($json) {
                    $documents[] = ['receipt' => $path, 'error' => $exception->getMessage()];
                } else {
                    $io->error(\sprintf('%s: %s', $path, $exception->getMessage()));
                }

                continue;
            }

            if ($receipt->isPending()) {
                ++$pending;
            }

            if ($json) {
                $documents[] = self::toArray($path, $receipt, $report, $wasComplete, $saved ? $target : null);
            } else {
                $this->render($io, $output, $path, $receipt, $report, $wasComplete, $all, $saved, $dryRun, $target);
            }
        }

        if ($json) {
            $output->writeln(json_encode(\count($receipts) === 1 ? $documents[0] : $documents, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
        } elseif (\count($receipts) > 1) {
            $this->summary($io, \count($receipts), $pending, $failed);
        }

        if ($failed > 0) {
            return Command::FAILURE;
        }

        return $pending > 0 ? self::STILL_PENDING : Command::SUCCESS;
    }

    private function render(SymfonyStyle $io, OutputInterface $output, string $path, Receipt $receipt, UpgradeReport $report, bool $wasComplete, bool $all, bool $saved, bool $dryRun, string $target): void
    {
        $io->title($path);

        if ($wasComplete && !$all) {
            $io->text(\sprintf('Already %s (Bitcoin block %d): nothing to poll. Pass --all to collect the other calendars\' attestations.', Formatter::status(Status::Complete), $receipt->bitcoinBlockHeight()));
            $io->newLine();

            return;
        }

        if ($report->results === []) {
            $io->warning('This proof has no pending attestation left to poll, yet it is not complete. It cannot be upgraded.');

            return;
        }

        $io->table(
            ['Calendar', 'Commitment', 'Answer'],
            array_map(static fn(CalendarUpgradeResult $result): array => [
                $result->calendarUrl,
                Formatter::hex($result->commitment, $output->isVerbose()),
                self::answer($result),
            ], $report->results),
        );

        if ($report->blockHeaderSource !== null) {
            $io->text(\sprintf('<fg=gray>Answers checked against the blockchain through %s before being merged.</>', $report->blockHeaderSource));
            $io->newLine();
        }

        if ($receipt->isComplete()) {
            $confirmed = \count(array_filter($report->results, static fn(CalendarUpgradeResult $r): bool => $r->blockHeight !== null));
            $detail = \count($report->results) > 1 ? \sprintf(' Confirmed through %d of %d calendars.', $confirmed, \count($report->results)) : '';
            $height = $receipt->bitcoinBlockHeight();

            if (!$wasComplete) {
                $io->success(\sprintf('Now complete: anchored in Bitcoin block %d.%s%s', $height, $detail, self::savedSuffix($saved, $dryRun, $target)));
            } elseif ($report->changed()) {
                $io->success(\sprintf('Complete (Bitcoin block %d): %s merged.%s%s', $height, Formatter::plural($report->count(UpgradeOutcome::Upgraded), 'new attestation'), $detail, self::savedSuffix($saved, $dryRun, $target)));
            } else {
                $io->note(\sprintf('Complete (Bitcoin block %d), nothing new from the other calendars.%s', $height, $detail));
            }

            return;
        }

        if ($report->changed()) {
            $io->note(\sprintf('New attestations merged, but the proof is still pending.%s', self::savedSuffix($saved, $dryRun, $target)));

            return;
        }

        $reasons = [];

        if ($report->count(UpgradeOutcome::Pending) > 0) {
            $reasons[] = 'Bitcoin confirmation usually takes a few hours; try again later.';
        }

        if ($report->count(UpgradeOutcome::Failed) > 0) {
            $reasons[] = 'Some calendars could not be reached; try again later or raise --timeout.';
        }

        if ($report->count(UpgradeOutcome::Skipped) > 0) {
            $reasons[] = 'Some calendars were skipped because they are not on the whitelist (see --whitelist and "calendars").';
        }

        if ($report->count(UpgradeOutcome::Rejected) > 0) {
            $reasons[] = 'A calendar answered with a proof that does not hold up (another digest, or a block that does not commit to it); its answer was discarded.';
        }

        if ($report->count(UpgradeOutcome::Unconfirmed) > 0) {
            $reasons[] = 'A calendar\'s block is still too shallow; its answer will be merged once it reaches --min-confirmations, try again later.';
        }

        if ($report->count(UpgradeOutcome::Unverifiable) > 0) {
            $reasons[] = 'A calendar\'s answer could not be checked against the blockchain; try again later, pick another --explorer, or pass --no-verify to merge it unchecked.';
        }

        $io->note(['Still pending, nothing new to save.', ...$reasons]);
    }

    private static function answer(CalendarUpgradeResult $result): string
    {
        return match ($result->outcome) {
            UpgradeOutcome::Upgraded => $result->blockHeight !== null
                ? \sprintf('<fg=green;options=bold>upgraded</> — Bitcoin block %d%s', $result->blockHeight, self::verifiedSuffix($result))
                : '<fg=green>upgraded</> — new attestations, not yet on Bitcoin',
            UpgradeOutcome::Unchanged => '<fg=green>unchanged</> — answered with what the proof already holds',
            UpgradeOutcome::Pending => '<fg=yellow>still pending</> — not confirmed yet',
            UpgradeOutcome::Failed => \sprintf('<fg=red>failed</> — %s', $result->error),
            UpgradeOutcome::Rejected => \sprintf('<fg=red>rejected</> — %s', $result->error),
            UpgradeOutcome::Unconfirmed => \sprintf('<fg=yellow>unconfirmed</> — %s, not merged yet', $result->error),
            UpgradeOutcome::Unverifiable => \sprintf('<fg=red>unverifiable</> — %s, not merged', $result->error),
            UpgradeOutcome::Skipped => '<fg=magenta>skipped</> — not on the whitelist, not contacted',
            UpgradeOutcome::Confirmed => \sprintf('<fg=green>confirmed</> — already anchored in Bitcoin block %d, not polled', $result->blockHeight),
        };
    }

    private static function verifiedSuffix(CalendarUpgradeResult $result): string
    {
        if ($result->verified() !== true) {
            return ' <fg=gray>(not checked)</>';
        }

        return \sprintf(', <fg=green>verified</> (%s)', Formatter::plural($result->confirmations() ?? 0, 'confirmation'));
    }

    private static function savedSuffix(bool $saved, bool $dryRun, string $target): string
    {
        if ($saved) {
            return \sprintf(' Saved to %s.', $target);
        }

        return $dryRun ? ' Not saved (--dry-run).' : '';
    }

    private function summary(SymfonyStyle $io, int $total, int $pending, int $failed): void
    {
        $complete = $total - $pending - $failed;
        $parts = [\sprintf('%d complete', $complete)];

        if ($pending > 0) {
            $parts[] = \sprintf('%d still pending', $pending);
        }

        if ($failed > 0) {
            $parts[] = \sprintf('%d in error', $failed);
        }

        $io->section('Summary');
        $io->text(\sprintf('%s: %s.', Formatter::plural($total, 'proof'), implode(', ', $parts)));
        $io->newLine();
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(string $path, Receipt $receipt, UpgradeReport $report, bool $wasComplete, ?string $savedTo): array
    {
        return [
            'receipt' => $path,
            'status' => Formatter::statusName($receipt->status()),
            'bitcoin_block_height' => $receipt->bitcoinBlockHeight(),
            'was_already_complete' => $wasComplete,
            'changed' => $report->changed(),
            'saved_to' => $savedTo,
            'block_header_source' => $report->blockHeaderSource,
            'calendars' => array_map(static fn(CalendarUpgradeResult $r): array => [
                'url' => $r->calendarUrl,
                'commitment' => $r->commitmentHex(),
                'outcome' => strtolower($r->outcome->name),
                'block_height' => $r->blockHeight,
                'claimed_block_height' => $r->claimedBlockHeight(),
                'verified' => $r->verified(),
                'confirmations' => $r->confirmations(),
                'error' => $r->error,
            ], $report->results),
        ];
    }
}
