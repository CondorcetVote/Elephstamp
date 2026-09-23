<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Command;

use CondorcetVote\ElephStamp\Calendar\CalendarWhitelist;
use CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor;
use CondorcetVote\ElephStamp\Console\Inspection\{CalendarSubmission, ProofInspection, ProofInspector, SubmissionPoint, UnknownNotary};
use CondorcetVote\ElephStamp\Console\{ClientOptions, Formatter};
use CondorcetVote\ElephStamp\Exception\ElephStampException;
use CondorcetVote\ElephStamp\{FileToStamp, Receipt, Status};
use Symfony\Component\Console\Attribute\{Argument, AsCommand, Option};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'info',
    description: 'Explain a proof: file digest, status, and what each calendar submission is up to',
    help: <<<'HELP'
        Reads one or more <comment>.ots</comment> proofs (no network access) and reports, for each:

          * the hashed file's digest, and whether it still matches the original file
            (found automatically as <comment><proof without .ots></comment>, or given with <comment>--file</comment>);
          * the overall status: <comment>pending</comment> or <comment>complete</comment>;
          * the digest actually submitted to the calendars, and whether it hides the file
            digest behind a privacy nonce;
          * every calendar submission: which calendar, when it recorded the commitment,
            whether it has been confirmed and whether <info>upgrade</info> may still contact it;
          * every Bitcoin attestation, with the id of the transaction carrying the
            commitment and the block's merkle root, as explorers show them.

        Block heights are what the proof <comment>claims</comment>: this tool never contacts a Bitcoin
        node. Calendar commitments are abbreviated in the table; <comment>-v</comment> prints them in
        full. For the raw proof tree with every intermediate hash, use <info>tree</info>.

        Exit codes: <info>0</info> fine, <info>1</info> bad usage, a proof could not be read, or a digest mismatch was found.
        HELP,
    usages: [
        'contract.pdf.ots',
        'contract.pdf.ots --file archive/contract-2024.pdf',
        'proofs/*.ots --json',
        'proof.ots -l https://*.internal.example',
    ],
)]
final class InfoCommand
{
    /**
     * @param list<string> $receipts
     * @param list<string> $whitelist
     */
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        #[Argument(description: 'Proof file(s) (.ots) to inspect')]
        array $receipts,
        #[Option(description: 'Original file to check the digest against (single proof only)')]
        ?string $file = null,
        #[Option(description: 'Print machine-readable JSON instead of the report')]
        bool $json = false,
        #[Option(description: 'Extra host pattern "upgrade" may contact (repeatable), e.g. https://*.internal.example', shortcut: 'l')]
        array $whitelist = [],
        #[Option(description: 'Drop the built-in whitelist of public calendars; only --whitelist patterns remain', name: 'no-default-whitelist')]
        bool $noDefaultWhitelist = false,
    ): int {
        if ($file !== null && \count($receipts) > 1) {
            $io->error('--file only makes sense with a single proof.');

            return Command::FAILURE;
        }

        $options = new ClientOptions(whitelist: $whitelist, useDefaultWhitelist: !$noDefaultWhitelist);

        try {
            // Same rule as ElephStamp: the calendars stamps go to are always upgradable.
            $inspector = new ProofInspector(new CalendarWhitelist([...$options->resolvedWhitelist(), ...$options->resolvedCalendarUrls()]));
        } catch (ElephStampException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $exitCode = Command::SUCCESS;
        $documents = [];

        foreach ($receipts as $path) {
            try {
                $inspection = $inspector->inspect(Receipt::fromPath($path));
            } catch (ElephStampException $exception) {
                $exitCode = Command::FAILURE;

                if ($json) {
                    $documents[] = ['receipt' => $path, 'error' => $exception->getMessage()];
                } else {
                    $io->error(\sprintf('%s: %s', $path, $exception->getMessage()));
                }

                continue;
            }

            $original = $file ?? self::siblingFile($path);
            $matches = $original === null ? null : self::digestMatches($inspection, $original);
            $expected = $file ?? self::siblingCandidate($path);

            if ($matches === false) {
                $exitCode = Command::FAILURE;
            }

            if ($json) {
                $documents[] = self::toArray($path, $inspection, $original, $matches);
            } else {
                $this->render($io, $output, $path, $inspection, $original, $expected, $matches);
            }
        }

        if ($json) {
            $output->writeln(json_encode(\count($receipts) === 1 ? $documents[0] : $documents, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
        }

        return $exitCode;
    }

    private function render(SymfonyStyle $io, OutputInterface $output, string $path, ProofInspection $inspection, ?string $original, ?string $expected, ?bool $matches): void
    {
        $receipt = $inspection->receipt;
        // Commitments are long and repeated per calendar, so they are the only
        // values abbreviated; -v prints them in full.
        $full = $output->isVerbose();

        $io->title($path);

        if ($matches === false) {
            // The proof may well be valid, but it is not a proof of *this*
            // file: that must be the first thing the reader sees.
            $io->block(
                [
                    'DIGEST MISMATCH',
                    \sprintf('%s is not the file this proof was made for.', $original),
                    \sprintf('Its %s digest differs from the one the proof commits to; the proof says nothing about this file.', $receipt->hashOperation()->describe()),
                ],
                type: null,
                style: 'fg=white;bg=red;options=bold',
                padding: true,
            );
        }

        $io->definitionList(
            ['Status' => self::statusLine($inspection)],
            ['File hash' => $receipt->hashOperation()->describe()],
            ['File digest' => $receipt->fileDigestHex()],
            ['Submitted digest' => self::submissionLine($inspection->submissionPoint)],
            ['Original file' => self::originalLine($original, $expected, $matches)],
            ['Proof size' => Formatter::bytes($inspection->sizeInBytes)],
        );

        $io->section(\sprintf('Calendar submissions (%d)', \count($inspection->submissions)));

        if ($inspection->submissions === []) {
            $io->text('None: this proof holds no pending attestation.');
            $io->newLine();
        } else {
            $io->table(
                ['Calendar', 'Recorded at (calendar clock, UTC)', 'Commitment', 'State'],
                array_map(static fn(CalendarSubmission $s): array => [
                    $s->calendarUrl,
                    $s->recordedAt?->format('Y-m-d H:i:s') ?? '<fg=gray>unknown</>',
                    $s->commitment === null ? '<fg=gray>unknown (below an unsupported operation)</>' : Formatter::hex($s->commitment, $full),
                    self::submissionState($s, $inspection->status()),
                ], $inspection->submissions),
            );
        }

        $io->section(\sprintf('Bitcoin attestations (%d)', \count($inspection->anchors)));

        if ($inspection->anchors === []) {
            $io->text('None yet: no calendar has anchored this commitment in a block so far.');
            $io->newLine();
        } else {
            foreach ($inspection->anchors as $anchor) {
                $io->definitionList(...self::anchorLines($anchor));
            }

            $io->text([
                'Recomputed from the proof, not verified: open the transaction and check the block it sits in,',
                'or open the block by its height and compare its merkle root (explorers do not search by merkle root).',
            ]);
            $io->newLine();
        }

        if ($inspection->unknownNotaries !== []) {
            $io->section(\sprintf('Unsupported attestations (%d)', \count($inspection->unknownNotaries)));
            $io->listing(array_map(
                static fn(UnknownNotary $n): string => \sprintf('tag %s, %d-byte payload (kept verbatim, not interpreted)', $n->tagHex(), $n->payloadLength),
                $inspection->unknownNotaries,
            ));
        }

        if (!$full && $inspection->submissions !== []) {
            $io->text('<fg=gray>Commitments are abbreviated; pass -v for full values, or use "tree" for the whole proof.</>');
            $io->newLine();
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private static function anchorLines(BitcoinAnchor $anchor): array
    {
        $height = $anchor->blockHeight();
        $transaction = $anchor->transaction;

        $lines = [
            ['Block height' => (string) $height],
            ['Transaction id' => $transaction?->txidHex() ?? '<fg=gray>unknown: the proof does not embed a recognisable transaction</>'],
            ['Merkle root' => $anchor->merkleRootHex() ?? '<fg=gray>unknown (below an unsupported operation)</>'],
        ];

        $lines[] = $transaction !== null
            ? ['Look it up' => \sprintf('https://mempool.space/tx/%s', $transaction->txidHex())]
            : ['Look it up' => \sprintf('https://mempool.space/block/%d', $height)];

        return $lines;
    }

    private static function statusLine(ProofInspection $inspection): string
    {
        if ($inspection->status() === Status::Complete) {
            $confirmed = \count($inspection->submissions) - \count($inspection->pendingSubmissions());
            $detail = \count($inspection->submissions) > 1
                ? \sprintf(' (confirmed through %d of %d calendars)', $confirmed, \count($inspection->submissions))
                : '';

            return \sprintf('%s — anchored in Bitcoin block %d%s', Formatter::status(Status::Complete), $inspection->bitcoinBlockHeight(), $detail);
        }

        $pending = \count($inspection->pendingSubmissions());
        $upgradable = \count(array_filter($inspection->pendingSubmissions(), static fn(CalendarSubmission $s): bool => $s->upgradable && $s->commitment !== null));

        if ($pending === 0) {
            return \sprintf('%s — no calendar left to poll; this proof cannot be upgraded', Formatter::status(Status::Pending));
        }

        if ($upgradable === 0) {
            return \sprintf('%s — %s, none of them upgradable (see below)', Formatter::status(Status::Pending), Formatter::plural($pending, 'calendar submission'));
        }

        return \sprintf('%s — waiting on %s; run "upgrade" to poll', Formatter::status(Status::Pending), Formatter::plural($pending, 'calendar'));
    }

    private static function submissionLine(?SubmissionPoint $point): string
    {
        if ($point === null) {
            return '<fg=gray>unknown: with a single calendar branch, the proof does not show where the calendar took over (see "tree")</>';
        }

        $digest = $point->digestHex() ?? '<fg=gray>not computable</>';

        if ($point->isFileDigest()) {
            return \sprintf('%s — the file digest itself, no nonce: <fg=yellow>the calendars know it</>', $digest);
        }

        $what = $point->isBatchRoot() ? 'root of a batch merkle tree' : 'the file digest';

        return $point->nonce !== null
            ? \sprintf('%s — %s, behind a <fg=green>privacy nonce</>', $digest, $what)
            : \sprintf('%s — %s, <fg=yellow>without nonce</>', $digest, $what);
    }

    private static function originalLine(?string $original, ?string $expected, ?bool $matches): string
    {
        if ($original === null) {
            return $expected === null
                ? '<fg=gray>not checked: the proof name does not end in .ots, so no original could be guessed (use --file)</>'
                : \sprintf('<fg=gray>not checked: %s not found next to the proof (use --file)</>', basename($expected));
        }

        return match ($matches) {
            true => \sprintf('%s — <fg=green>digest matches</>', $original),
            false => \sprintf('%s — <fg=red;options=bold>DIGEST MISMATCH</>: this proof is not for that file', $original),
            null => \sprintf('%s — <fg=red>unreadable</>', $original),
        };
    }

    private static function submissionState(CalendarSubmission $submission, Status $status): string
    {
        if ($submission->isConfirmed()) {
            return \sprintf('<fg=green>confirmed</> in block %s', implode(', ', $submission->confirmedBlockHeights));
        }

        // One Bitcoin attestation makes the proof complete; the other
        // calendars are then never polled again, so "upgradable" would be a lie.
        if ($status === Status::Complete) {
            return '<fg=gray>pending, no longer polled: the proof is already complete (see upgrade --all)</>';
        }

        if ($submission->commitment === null) {
            return '<fg=red>not upgradable</>: sits below an operation this tool cannot compute';
        }

        if (!$submission->upgradable) {
            return '<fg=red>not upgradable</>: calendar not on the whitelist (see --whitelist)';
        }

        return '<fg=yellow>pending</>, upgradable';
    }

    /**
     * Where the original file would sit if it were next to the proof.
     */
    private static function siblingCandidate(string $receiptPath): ?string
    {
        return str_ends_with($receiptPath, '.ots') ? substr($receiptPath, 0, -4) : null;
    }

    /**
     * The original file a proof was made for, when it sits next to it.
     */
    private static function siblingFile(string $receiptPath): ?string
    {
        $candidate = self::siblingCandidate($receiptPath);

        return $candidate !== null && is_file($candidate) ? $candidate : null;
    }

    /**
     * @return bool|null null when the file cannot be read
     */
    private static function digestMatches(ProofInspection $inspection, string $original): ?bool
    {
        try {
            $digest = FileToStamp::fromPath($original)->digest($inspection->receipt->hashOperation());
        } catch (ElephStampException) {
            return null;
        }

        return hash_equals($inspection->receipt->fileDigest(), $digest);
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(string $path, ProofInspection $inspection, ?string $original, ?bool $matches): array
    {
        return [
            'receipt' => $path,
            'status' => Formatter::statusName($inspection->status()),
            'bitcoin_block_height' => $inspection->bitcoinBlockHeight(),
            'file' => [
                'hash' => $inspection->receipt->hashOperation()->describe(),
                'digest' => $inspection->receipt->fileDigestHex(),
                'path' => $original,
                'digest_matches' => $matches,
            ],
            'proof_size_bytes' => $inspection->sizeInBytes,
            'submission' => $inspection->submissionPoint === null ? null : [
                'digest' => $inspection->submissionPoint->digestHex(),
                'privacy_nonce' => $inspection->submissionPoint->nonce !== null,
                'batch' => $inspection->submissionPoint->isBatchRoot(),
            ],
            'calendars' => array_map(static fn(CalendarSubmission $s): array => [
                'url' => $s->calendarUrl,
                'commitment' => $s->commitmentHex(),
                'recorded_at' => $s->recordedAt?->format(\DATE_ATOM),
                'confirmed' => $s->isConfirmed(),
                'block_heights' => $s->confirmedBlockHeights,
                'upgradable' => $s->upgradable && $s->commitment !== null,
            ], $inspection->submissions),
            'bitcoin_attestations' => array_map(static fn(BitcoinAnchor $a): array => [
                'block_height' => $a->blockHeight(),
                'transaction_id' => $a->transaction?->txidHex(),
                'merkle_root' => $a->merkleRootHex(),
            ], $inspection->anchors),
            'unknown_attestations' => array_map(static fn(UnknownNotary $n): array => [
                'tag' => $n->tagHex(),
                'payload_bytes' => $n->payloadLength,
            ], $inspection->unknownNotaries),
        ];
    }
}
