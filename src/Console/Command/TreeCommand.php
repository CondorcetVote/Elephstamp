<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Command;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation, TimeAttestation};
use CondorcetVote\ElephStamp\Console\Inspection\SubmissionPoint;
use CondorcetVote\ElephStamp\Exception\ElephStampException;
use CondorcetVote\ElephStamp\Operation\Operation;
use CondorcetVote\ElephStamp\{Receipt, Timestamp};
use Symfony\Component\Console\Attribute\{Argument, AsCommand, Option};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\{TreeHelper, TreeNode};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tree',
    description: 'Dump the whole proof: every operation and every intermediate hash',
    help: <<<'HELP'
        Prints the raw commitment tree of a proof, starting from the file digest: each
        operation (<comment>append</comment>, <comment>prepend</comment>, <comment>sha256</comment>, ...) followed by the hash it
        produces, down to the attestations. This is the view for checking a proof by hand.

        The digest actually sent to the calendars is shown as a <info>submitted to the calendars</info>
        node grouping their branches, and the client's own random nonce is flagged as
        <comment>privacy nonce</comment>. A proof with a single calendar branch has no such marker: the
        hand-over point cannot be told apart from the calendar's own operations.

        For a human summary of the same proof, use <info>info</info>.
        HELP,
    usages: [
        'contract.pdf.ots',
        'contract.pdf.ots --plain',
        'contract.pdf.ots --no-hashes',
    ],
)]
final class TreeCommand
{
    /**
     * @param list<string> $receipts
     */
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        #[Argument(description: 'Proof file(s) (.ots) to dump')]
        array $receipts,
        #[Option(description: 'Plain indented text, in the reference client\'s layout, instead of a drawn tree')]
        bool $plain = false,
        #[Option(description: 'Show the operations only, without the hash each one produces', name: 'no-hashes')]
        bool $noHashes = false,
    ): int {
        $exitCode = Command::SUCCESS;

        foreach ($receipts as $path) {
            try {
                $receipt = Receipt::fromPath($path);
            } catch (ElephStampException $exception) {
                $io->error(\sprintf('%s: %s', $path, $exception->getMessage()));
                $exitCode = Command::FAILURE;

                continue;
            }

            $io->title($path);

            if ($plain) {
                $output->writeln($receipt->describe(), OutputInterface::OUTPUT_RAW);
            } else {
                $timestamp = $receipt->detachedTimestampFile()->timestamp;
                $root = new TreeNode(
                    \sprintf('file %s digest <fg=cyan>%s</>', $receipt->hashOperation()->describe(), $receipt->fileDigestHex()),
                    self::children($timestamp, !$noHashes, SubmissionPoint::locate($timestamp)),
                );
                TreeHelper::createTree($output, $root)->render();
            }

            $io->newLine();
        }

        return $exitCode;
    }

    /**
     * Lay the tree out like the reference client: a linear chain of operations
     * stays flat, and only a real fork nests its branches. The calendar
     * branches are grouped under the digest submitted to them.
     *
     * @return list<TreeNode>
     */
    private static function children(Timestamp $node, bool $withHashes, ?SubmissionPoint $submission = null): array
    {
        if ($submission !== null && $node === $submission->node) {
            return [new TreeNode(self::submissionLabel($submission, $withHashes), self::children($node, $withHashes))];
        }

        $nodes = [];

        $attestations = $node->attestations();
        usort($attestations, static fn(TimeAttestation $a, TimeAttestation $b): int => $a->compareTo($b));

        foreach ($attestations as $attestation) {
            $nodes[] = new TreeNode(self::attestationLabel($attestation));
        }

        $ops = $node->operations();
        usort($ops, static fn(array $a, array $b): int => strcmp($a['op']->comparisonKey(), $b['op']->comparisonKey()));

        if (\count($ops) === 1) {
            $label = self::operationLabel($ops[0]['op'], $ops[0]['timestamp'], $withHashes);

            if ($submission !== null && $ops[0]['op'] === $submission->nonce) {
                $label .= ' <fg=gray>(privacy nonce)</>';
            }

            $nodes[] = new TreeNode($label);

            return [...$nodes, ...self::children($ops[0]['timestamp'], $withHashes, $submission)];
        }

        foreach ($ops as ['op' => $op, 'timestamp' => $child]) {
            $nodes[] = new TreeNode(self::operationLabel($op, $child, $withHashes), self::children($child, $withHashes));
        }

        return $nodes;
    }

    private static function operationLabel(Operation $op, Timestamp $result, bool $withHashes): string
    {
        $label = '<fg=yellow>' . explode(' ', $op->describe(), 2)[0] . '</>' . substr($op->describe(), \strlen(explode(' ', $op->describe(), 2)[0]));

        if (!$withHashes) {
            return $label;
        }

        return $label . ($result->msg === null
            ? ' <fg=gray>= (not computable)</>'
            : \sprintf(' <fg=gray>=</> <fg=cyan>%s</>', bin2hex($result->msg)));
    }

    private static function submissionLabel(SubmissionPoint $submission, bool $withHashes): string
    {
        $label = '<fg=green;options=bold>submitted to the calendars</>';

        if ($withHashes && $submission->digestHex() !== null) {
            $label .= \sprintf(' <fg=gray>=</> <fg=cyan>%s</>', $submission->digestHex());
        }

        return $label . ($submission->isFileDigest() ? ' <fg=gray>(the file digest itself, no nonce)</>' : '');
    }

    private static function attestationLabel(TimeAttestation $attestation): string
    {
        return match (true) {
            $attestation instanceof PendingAttestation => \sprintf('<fg=yellow;options=bold>pending attestation</> → %s', $attestation->uri),
            $attestation instanceof BitcoinAttestation => \sprintf('<fg=green;options=bold>bitcoin attestation</> → block %d', $attestation->blockHeight),
            default => '<fg=magenta>' . $attestation->describe() . '</>',
        };
    }
}
