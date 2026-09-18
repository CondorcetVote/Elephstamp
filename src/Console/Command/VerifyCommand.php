<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Command;

use CondorcetVote\ElephStamp\Console\{ClientFactory, ClientOptions, Formatter};
use CondorcetVote\ElephStamp\Exception\{ElephStampException, InvalidInputException};
use CondorcetVote\ElephStamp\Verify\{AnchorOutcome, AnchorVerification, Explorer, VerificationReport, Verdict, Verifier};
use CondorcetVote\ElephStamp\{FileToStamp, Receipt};
use Symfony\Component\Console\Attribute\{Argument, AsCommand, Option};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'verify',
    description: 'Check a proof against the Bitcoin blockchain, through a public block explorer',
    help: <<<'HELP'
        Recomputes everything a proof contains (file digest, operations, transaction,
        merkle branch) and asks a block explorer for the header of every block the proof
        names. The proof is <comment>verified</comment> when the block's merkle root equals the one the
        proof leads to, and the block is buried under <comment>--min-confirmations</comment> blocks.

        The original file is found next to the proof (<comment>contract.pdf</comment> for
        <comment>contract.pdf.ots</comment>), or given with <comment>--file</comment> or as a <comment>--digest</comment>. Without it, only the
        proof itself is checked, not that it is the proof of a particular file.

        The explorer is a third party you trust for block headers. Its raw header is
        parsed locally and its proof of work checked, and several explorers can be
        required to agree with repeated <comment>--explorer</comment> options.

        Exit codes: <info>0</info> verified; <info>1</info> failed (the file or a merkle root does not match), bad
        usage, or an unreadable proof; <info>2</info> not verifiable yet (pending, awaiting
        confirmations, or the explorer could not be reached).
        HELP,
    usages: [
        'contract.pdf.ots',
        'contract.pdf.ots --file archive/contract-2024.pdf',
        'contract.pdf.ots --digest 03ba204e…',
        'contract.pdf.ots --explorer blockstream',
        'contract.pdf.ots --explorer mempool --explorer blockstream',
        'contract.pdf.ots --explorer-url https://esplora.internal/api',
        'proofs/*.ots --min-confirmations 1 --json',
    ],
)]
final class VerifyCommand
{
    /**
     * Exit code when nothing is wrong but the proof cannot be verified yet.
     */
    public const int NOT_YET = 2;

    public function __construct(private readonly ClientFactory $clientFactory) {}

    /**
     * @param list<string> $receipts
     * @param list<string> $explorer
     * @param list<string> $explorerUrl
     */
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        #[Argument(description: 'Proof file(s) (.ots) to verify')]
        array $receipts,
        #[Option(description: 'Original file to check the proof against (single proof only)')]
        ?string $file = null,
        #[Option(description: 'Hex SHA-256 digest of the original, instead of the file (single proof only)')]
        ?string $digest = null,
        #[Option(description: 'Block explorer to ask: mempool (default) or blockstream. Repeat to require several to agree', shortcut: 'e', suggestedValues: ['mempool', 'blockstream'])]
        array $explorer = [],
        #[Option(description: 'Base URL of another Esplora-compatible explorer, e.g. a self-hosted one (repeatable, https only)', name: 'explorer-url')]
        array $explorerUrl = [],
        #[Option(description: 'Blocks a verifying block must be buried under, itself included', name: 'min-confirmations')]
        int $minConfirmations = Verifier::DEFAULT_REQUIRED_CONFIRMATIONS,
        #[Option(description: 'Seconds to wait for an explorer before giving up on it')]
        ?float $timeout = null,
        #[Option(description: 'Print machine-readable JSON instead of the report')]
        bool $json = false,
    ): int {
        if (($file !== null || $digest !== null) && \count($receipts) > 1) {
            $io->error('--file and --digest only make sense with a single proof.');

            return Command::FAILURE;
        }

        if ($file !== null && $digest !== null) {
            $io->error('Give either --file or --digest, not both.');

            return Command::FAILURE;
        }

        if ($minConfirmations < 1) {
            $io->error('--min-confirmations must be at least 1.');

            return Command::FAILURE;
        }

        try {
            $explorers = array_map(
                static fn(string $name): Explorer => Explorer::tryFrom($name) ?? throw new InvalidInputException(\sprintf('Unknown explorer "%s"; known: %s', $name, implode(', ', array_column(Explorer::cases(), 'value')))),
                $explorer,
            );

            $client = $this->clientFactory->create(new ClientOptions(timeout: $timeout, explorers: $explorers, explorerUrls: $explorerUrl));
        } catch (ElephStampException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $exitCode = Command::SUCCESS;
        $documents = [];

        foreach ($receipts as $path) {
            try {
                $receipt = Receipt::fromPath($path);
                [$original, $subject] = self::subject($path, $file, $digest);
                $report = $client->verify($receipt, $subject, $minConfirmations);
            } catch (ElephStampException $exception) {
                $exitCode = Command::FAILURE;

                if ($json) {
                    $documents[] = ['receipt' => $path, 'error' => $exception->getMessage()];
                } else {
                    $io->error(\sprintf('%s: %s', $path, $exception->getMessage()));
                }

                continue;
            }

            $exitCode = max($exitCode, self::exitCode($report->verdict()));

            if ($json) {
                $documents[] = self::toArray($path, $receipt, $report, $original);
            } else {
                $this->render($io, $output, $path, $receipt, $report, $original);
            }
        }

        if ($json) {
            $output->writeln(json_encode(\count($receipts) === 1 ? $documents[0] : $documents, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
        }

        return $exitCode;
    }

    /**
     * What the proof is checked against: an explicit file or digest, or the
     * file sitting next to the proof.
     *
     * @return array{?string, ?FileToStamp} a label for the subject, and the subject
     */
    private static function subject(string $receiptPath, ?string $file, ?string $digest): array
    {
        if ($digest !== null) {
            $hex = strtolower($digest);
            $raw = ctype_xdigit($hex) && \strlen($hex) === 64 ? hex2bin($hex) : false;

            if ($raw === false) {
                throw new InvalidInputException('--digest must be a 64-character hex SHA-256 digest');
            }

            return ['digest ' . $hex, FileToStamp::fromDigest($raw)];
        }

        if ($file === null && str_ends_with($receiptPath, '.ots') && is_file(substr($receiptPath, 0, -4))) {
            $file = substr($receiptPath, 0, -4);
        }

        return $file === null ? [null, null] : [$file, FileToStamp::fromPath($file)];
    }

    private static function exitCode(Verdict $verdict): int
    {
        return match ($verdict) {
            Verdict::Verified => Command::SUCCESS,
            Verdict::Failed => Command::FAILURE,
            Verdict::AwaitingConfirmations, Verdict::Pending, Verdict::Inconclusive => self::NOT_YET,
        };
    }

    private function render(SymfonyStyle $io, OutputInterface $output, string $path, Receipt $receipt, VerificationReport $report, ?string $original): void
    {
        $io->title($path);

        $this->banner($io, $report, $original);

        $io->definitionList(
            ['Original' => self::originalLine($original, $report->fileMatches)],
            ['Proof' => \sprintf('%s digest %s, %s recomputed offline', $receipt->hashOperation()->describe(), $receipt->fileDigestHex(), Formatter::plural(\count($report->anchors), 'Bitcoin attestation'))],
            ['Block headers' => \sprintf('%s — a third party, trusted for block headers only', $report->source)],
            ['Required depth' => Formatter::plural($report->requiredConfirmations, 'confirmation')],
        );

        if ($report->anchors === []) {
            $io->text('No Bitcoin attestation to check: the proof is still pending. Run "upgrade" first.');
            $io->newLine();

            return;
        }

        $io->table(
            ['Block', 'Merkle root (proof)', 'Merkle root (block)', 'Mined at (UTC)', 'Confirmations', 'Result'],
            array_map(static fn(AnchorVerification $v): array => [
                (string) $v->blockHeight(),
                $v->anchor->merkleRootHex() === null ? '<fg=gray>unknown</>' : Formatter::abbreviate($v->anchor->merkleRootHex()),
                $v->header === null ? '<fg=gray>—</>' : Formatter::abbreviate($v->header->merkleRootHex()),
                $v->header?->time->format('Y-m-d H:i:s') ?? '<fg=gray>—</>',
                $v->confirmations === null ? '<fg=gray>—</>' : (string) $v->confirmations,
                self::result($v, $report->requiredConfirmations),
            ], $report->anchors),
        );

        if ($output->isVerbose()) {
            foreach ($report->anchors as $v) {
                if ($v->header !== null) {
                    $io->text(\sprintf('Block %d: hash %s, merkle root %s%s', $v->blockHeight(), $v->header->hashHex(), $v->header->merkleRootHex(), $v->header->rawHeader === null ? '' : ', proof of work checked from the raw header'));
                }
            }

            $io->newLine();
        } else {
            $io->text('<fg=gray>Merkle roots are abbreviated; pass -v for full values and block hashes.</>');
            $io->newLine();
        }
    }

    private function banner(SymfonyStyle $io, VerificationReport $report, ?string $original): void
    {
        $subject = $original ?? 'The stamped data';
        $verdict = $report->verdict();

        [$style, $lines] = match ($verdict) {
            Verdict::Verified => ['fg=black;bg=green;options=bold', [
                'VERIFIED',
                \sprintf('%s existed before %s (Bitcoin block %d).', $subject, $report->attestedAt()?->format('Y-m-d H:i:s \U\T\C'), $report->attestingAnchor()?->blockHeight()),
                $original === null ? 'No original file was checked: this proves the proof, not that it belongs to a given file (use --file).' : null,
            ]],
            Verdict::AwaitingConfirmations => ['fg=black;bg=yellow;options=bold', [
                'AWAITING CONFIRMATIONS',
                'The block matches the proof but is too recent; verify again in a while.',
            ]],
            Verdict::Failed => ['fg=white;bg=red;options=bold', [
                'VERIFICATION FAILED',
                $report->fileMatches === false
                    ? \sprintf('%s is not the file this proof was made for: its digest differs from the one the proof commits to.', $subject)
                    : 'A block\'s merkle root differs from the one the proof leads to: the proof is corrupt, forged, or names the wrong block.',
            ]],
            Verdict::Pending => ['fg=black;bg=yellow;options=bold', [
                'PENDING',
                'The proof has no Bitcoin attestation yet; run "upgrade" first.',
            ]],
            Verdict::Inconclusive => ['fg=white;bg=gray;options=bold', [
                'INCONCLUSIVE',
                'No block header could be checked; see the details below and try again later.',
            ]],
        };

        $io->block(array_filter($lines, static fn(?string $line): bool => $line !== null), type: null, style: $style, padding: true);
    }

    private static function originalLine(?string $original, ?bool $matches): string
    {
        if ($original === null) {
            return '<fg=gray>not checked: none found next to the proof (use --file or --digest)</>';
        }

        return $matches === true
            ? \sprintf('%s — <fg=green>digest matches the proof</>', $original)
            : \sprintf('%s — <fg=red;options=bold>DIGEST MISMATCH</>', $original);
    }

    private static function result(AnchorVerification $verification, int $required): string
    {
        return match ($verification->outcome) {
            AnchorOutcome::Verified => '<fg=green;options=bold>verified</> — merkle roots match',
            AnchorOutcome::AwaitingConfirmations => \sprintf('<fg=yellow>matches</>, awaiting confirmations (%d of %d)', $verification->confirmations, $required),
            AnchorOutcome::MerkleRootMismatch => '<fg=red;options=bold>MISMATCH</> — the block does not commit to this proof',
            AnchorOutcome::BlockUnavailable => \sprintf('<fg=red>unavailable</> — %s', $verification->error),
            AnchorOutcome::NotComputable => '<fg=gray>not computable</> — below an operation this tool cannot compute',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(string $path, Receipt $receipt, VerificationReport $report, ?string $original): array
    {
        return [
            'receipt' => $path,
            'verdict' => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $report->verdict()->name) ?? ''),
            'attested_at' => $report->attestedAt()?->format(\DATE_ATOM),
            'attesting_block_height' => $report->attestingAnchor()?->blockHeight(),
            'file' => [
                'hash' => $receipt->hashOperation()->describe(),
                'digest' => $receipt->fileDigestHex(),
                'subject' => $original,
                'digest_matches' => $report->fileMatches,
            ],
            'source' => $report->source,
            'required_confirmations' => $report->requiredConfirmations,
            'bitcoin_attestations' => array_map(static fn(AnchorVerification $v): array => [
                'block_height' => $v->blockHeight(),
                'outcome' => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $v->outcome->name) ?? ''),
                'proof_merkle_root' => $v->anchor->merkleRootHex(),
                'block_merkle_root' => $v->header?->merkleRootHex(),
                'block_hash' => $v->header?->hashHex(),
                'block_time' => $v->header?->time->format(\DATE_ATOM),
                'confirmations' => $v->confirmations,
                'transaction_id' => $v->anchor->transaction?->txidHex(),
                'error' => $v->error,
            ], $report->anchors),
        ];
    }
}
