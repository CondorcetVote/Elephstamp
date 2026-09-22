<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use DateTimeImmutable;

/**
 * The result of {@see Verifier::verify()}: the file check, one entry per
 * Bitcoin attestation, and the conclusion drawn from them.
 */
final class VerificationReport
{
    /**
     * @param bool|null                $fileMatches           whether the file's digest is the one the proof commits to; null when no file was given
     * @param list<AnchorVerification> $anchors
     * @param int                      $requiredConfirmations the depth an attestation needed to count as verified
     * @param string                   $source                the header source that was consulted
     */
    public function __construct(
        public readonly ?bool $fileMatches,
        public readonly array $anchors,
        public readonly int $requiredConfirmations,
        public readonly string $source,
    ) {}

    /**
     * The conclusion drawn from the file check and the attestations.
     *
     * One verified attestation is enough: a proof is a bundle of independent
     * paths to the blockchain, so a path that does not match its block (a
     * calendar that answered nonsense, a corrupt branch) does not undo one
     * that does. Such mismatches stay visible in {@see $anchors} and in
     * {@see mismatches()}; only when nothing vouches for the proof does a
     * mismatch make the verdict {@see Verdict::Failed}.
     */
    public function verdict(): Verdict
    {
        if ($this->fileMatches === false) {
            return Verdict::Failed;
        }

        if ($this->count(AnchorOutcome::Verified) > 0) {
            return Verdict::Verified;
        }

        if ($this->count(AnchorOutcome::MerkleRootMismatch) > 0) {
            return Verdict::Failed;
        }

        if ($this->anchors === []) {
            return Verdict::Pending;
        }

        if ($this->count(AnchorOutcome::AwaitingConfirmations) > 0) {
            return Verdict::AwaitingConfirmations;
        }

        return Verdict::Inconclusive;
    }

    /**
     * The attestations whose block does not commit to the proof, whatever
     * the verdict. Worth reporting even on a verified proof: one of its
     * calendars handed out something wrong.
     *
     * @return list<AnchorVerification>
     */
    public function mismatches(): array
    {
        return $this->filter(AnchorOutcome::MerkleRootMismatch);
    }

    public function isVerified(): bool
    {
        return $this->verdict() === Verdict::Verified;
    }

    /**
     * The earliest block that verifies the proof, i.e. the one whose time is
     * the attested date. Null unless the verdict is {@see Verdict::Verified}.
     */
    public function attestingAnchor(): ?AnchorVerification
    {
        if ($this->verdict() !== Verdict::Verified) {
            return null;
        }

        $best = null;

        foreach ($this->filter(AnchorOutcome::Verified) as $verification) {
            if ($best === null || $verification->blockHeight() < $best->blockHeight()) {
                $best = $verification;
            }
        }

        return $best;
    }

    /**
     * The date the file is proven to have existed before: the time of the
     * earliest verifying block. Null unless verified.
     */
    public function attestedAt(): ?DateTimeImmutable
    {
        return $this->attestingAnchor()?->header?->time;
    }

    public function count(AnchorOutcome $outcome): int
    {
        return \count($this->filter($outcome));
    }

    /**
     * @return list<AnchorVerification>
     */
    public function filter(AnchorOutcome $outcome): array
    {
        return array_values(array_filter(
            $this->anchors,
            static fn(AnchorVerification $verification): bool => $verification->outcome === $outcome,
        ));
    }
}
