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
        $fileMatches = $file === null ? null : hash_equals($receipt->fileDigest(), $file->digest($receipt->hashOperation()));

        return new VerificationReport(
            $fileMatches,
            $this->checkAnchors($receipt->bitcoinAnchors()),
            $this->requiredConfirmations,
            $this->source->describe(),
        );
    }

    /**
     * Check Bitcoin attestations against the blocks they name, whatever
     * proof they come from: a receipt, or a calendar's answer about to be
     * merged into one.
     *
     * The chain tip is fetched once for the whole batch, then one header per
     * attestation. Never throws for a source failure: the affected
     * attestations are reported as {@see AnchorOutcome::BlockUnavailable}.
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

        return array_map(fn(BitcoinAnchor $anchor): AnchorVerification => $this->check($anchor, $tip), $anchors);
    }

    private function check(BitcoinAnchor $anchor, int $tip): AnchorVerification
    {
        if ($anchor->merkleRoot === null) {
            return new AnchorVerification($anchor, AnchorOutcome::NotComputable);
        }

        try {
            $header = $this->source->blockHeader($anchor->blockHeight());
        } catch (BlockSourceException $exception) {
            return new AnchorVerification($anchor, AnchorOutcome::BlockUnavailable, error: $exception->getMessage());
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
