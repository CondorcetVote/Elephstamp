<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor;
use CondorcetVote\ElephStamp\Exception\{BlockSourceException, InvalidInputException};
use CondorcetVote\ElephStamp\{FileToStamp, Receipt};

/**
 * Checks a receipt against the Bitcoin blockchain, through a {@see BlockHeaderSource}.
 *
 * Everything inside the proof is recomputed locally: file digest, operations,
 * transaction, merkle branch. The only external question is "what is the
 * merkle root of block N?", asked to the source for each Bitcoin attestation.
 * How much the answer can be trusted depends on the source: a public
 * explorer is a third party, a node you run is not.
 */
final class Verifier
{
    /**
     * Blocks a verifying block must be buried under, itself included, before
     * the attestation counts as final.
     */
    public const int DEFAULT_REQUIRED_CONFIRMATIONS = 6;

    public function __construct(
        private readonly BlockHeaderSource $source,
        private readonly int $requiredConfirmations = self::DEFAULT_REQUIRED_CONFIRMATIONS,
    ) {
        if ($requiredConfirmations < 1) {
            throw new InvalidInputException('requiredConfirmations must be at least 1');
        }
    }

    /**
     * Verify a receipt, and optionally that it is the proof of a given file.
     *
     * Never throws for a source failure: the affected attestations are
     * reported as {@see AnchorOutcome::BlockUnavailable}.
     */
    public function verify(Receipt $receipt, ?FileToStamp $file = null): VerificationReport
    {
        return $this->verifyMany([$receipt], [$file])[0];
    }

    /**
     * Verify several receipts in one pass, related or not.
     *
     * The chain tip is fetched once for the whole batch and each block header
     * once, however many receipts name it: receipts stamped together and
     * confirmed in the same block cost a single header, while unrelated
     * receipts simply add theirs. Every receipt still gets its own report,
     * aligned with $receipts.
     *
     * Never throws for a source failure: the affected attestations are
     * reported as {@see AnchorOutcome::BlockUnavailable}.
     *
     * @param list<Receipt>                $receipts
     * @param array<int, FileToStamp|null> $files    the file each receipt should be the proof of, keyed like $receipts; a missing or null entry only checks the proof itself
     *
     * @throws InvalidInputException if $files names an index with no receipt
     *
     * @return list<VerificationReport> one per receipt, in the same order
     */
    public function verifyMany(array $receipts, array $files = []): array
    {
        foreach (array_keys($files) as $index) {
            if (!isset($receipts[$index])) {
                throw new InvalidInputException(\sprintf('No receipt at index %d for the file given there', $index));
            }
        }

        $fileMatches = [];
        $anchors = [];
        $owners = [];

        foreach ($receipts as $index => $receipt) {
            $file = $files[$index] ?? null;
            $fileMatches[$index] = $file === null ? null : hash_equals($receipt->fileDigest(), $file->digest($receipt->hashOperation()));

            foreach ($receipt->bitcoinAnchors() as $anchor) {
                $anchors[] = $anchor;
                $owners[] = $index;
            }
        }

        $verifications = array_fill(0, \count($receipts), []);

        foreach ($this->checkAnchors($anchors) as $position => $verification) {
            $verifications[$owners[$position]][] = $verification;
        }

        $source = $this->source->describe();

        return array_map(
            fn(int $index): VerificationReport => new VerificationReport($fileMatches[$index], $verifications[$index], $this->requiredConfirmations, $source),
            array_keys($receipts),
        );
    }

    /**
     * Check Bitcoin attestations against the blocks they name, whatever
     * proof they come from: a receipt, or a calendar's answer about to be
     * merged into one.
     *
     * The chain tip is fetched once for the whole batch, and each block
     * header once however many attestations name it (an unavailable block is
     * not asked for again within the batch either). Never throws for a
     * source failure: the affected attestations are reported as
     * {@see AnchorOutcome::BlockUnavailable}.
     *
     * @param list<BitcoinAnchor> $anchors
     *
     * @return list<AnchorVerification> aligned with $anchors
     */
    public function checkAnchors(array $anchors): array
    {
        if ($anchors === []) {
            return [];
        }

        try {
            $tip = $this->source->tipHeight();
        } catch (BlockSourceException $exception) {
            // Without a tip nothing can be counted; the headers would most
            // likely fail too, so report every attestation as unavailable.
            return array_map(static fn(BitcoinAnchor $anchor): AnchorVerification => new AnchorVerification($anchor, AnchorOutcome::BlockUnavailable, error: $exception->getMessage()), $anchors);
        }

        $headers = [];
        $verifications = [];

        foreach ($anchors as $anchor) {
            $verifications[] = $this->check($anchor, $tip, $headers);
        }

        return $verifications;
    }

    /**
     * @param array<int, BlockHeader|BlockSourceException> $headers what the source answered for each height already asked in this batch
     */
    private function check(BitcoinAnchor $anchor, int $tip, array &$headers): AnchorVerification
    {
        if ($anchor->merkleRoot === null) {
            return new AnchorVerification($anchor, AnchorOutcome::NotComputable);
        }

        $height = $anchor->blockHeight();

        if (!\array_key_exists($height, $headers)) {
            try {
                $headers[$height] = $this->source->blockHeader($height);
            } catch (BlockSourceException $exception) {
                $headers[$height] = $exception;
            }
        }

        $header = $headers[$height];

        if ($header instanceof BlockSourceException) {
            return new AnchorVerification($anchor, AnchorOutcome::BlockUnavailable, error: $header->getMessage());
        }

        $confirmations = max(0, $tip - $anchor->blockHeight() + 1);

        if (!hash_equals($header->merkleRoot, $anchor->merkleRoot)) {
            return new AnchorVerification($anchor, AnchorOutcome::MerkleRootMismatch, $header, $confirmations);
        }

        return new AnchorVerification(
            $anchor,
            $confirmations >= $this->requiredConfirmations ? AnchorOutcome::Verified : AnchorOutcome::AwaitingConfirmations,
            $header,
            $confirmations,
        );
    }
}
