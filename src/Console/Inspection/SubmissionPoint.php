<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console\Inspection;

use CondorcetVote\ElephStamp\Operation\{Append, Operation};
use CondorcetVote\ElephStamp\Timestamp;

/**
 * Where, in a proof tree, the client handed its digest over to the calendars.
 *
 * A proof does not record that point explicitly. It is inferred from the
 * layout: everything the client computes (privacy nonce, batch merkle tree)
 * is one linear chain from the file digest, and every calendar grafts its own
 * branch onto the digest it received. The first fork of the tree, or a node
 * several calendars attested directly, is therefore the submitted digest. A
 * proof with a single branch (one calendar, or a reference proof collapsed to
 * its bitcoin path) has no such point that can be told apart from the
 * calendar's own operations: {@see locate()} then returns null rather than guess.
 */
final class SubmissionPoint
{
    /**
     * Length of the privacy nonce the reference client and ElephStamp append to the file digest.
     */
    private const int NONCE_LENGTH = 16;

    /**
     * Operations of the nonce step: the append, then the hash.
     */
    private const int NONCE_STEP_LENGTH = 2;

    /**
     * @param Timestamp       $node             the node holding the digest every calendar received
     * @param list<Operation> $clientOperations the client-side operations leading from the file digest to $node
     * @param Append|null     $nonce            the client's privacy nonce operation, when there is one
     */
    private function __construct(
        public readonly Timestamp $node,
        public readonly array $clientOperations,
        public readonly ?Append $nonce,
    ) {}

    /**
     * Find the submission point below a proof's root (the file digest node).
     */
    public static function locate(Timestamp $root): ?self
    {
        $node = $root;
        $path = [];

        while ($node->attestations() === [] && \count($node->operations()) === 1) {
            $step = $node->operations()[0];
            $path[] = $step['op'];
            $node = $step['timestamp'];
        }

        $forks = $node->attestations() === [] && \count($node->operations()) >= 2;
        $sharedByCalendars = \count($node->attestations()) >= 2;

        if (!$forks && !$sharedByCalendars) {
            return null;
        }

        $first = $path[0] ?? null;
        $nonce = $first instanceof Append && \strlen($first->argument) === self::NONCE_LENGTH ? $first : null;

        return new self($node, $path, $nonce);
    }

    /**
     * The submitted digest, or null below an operation that cannot be computed.
     */
    public function digest(): ?string
    {
        return $this->node->msg;
    }

    public function digestHex(): ?string
    {
        return $this->node->msg === null ? null : bin2hex($this->node->msg);
    }

    /**
     * Whether the file digest itself went to the calendars: no nonce, no batch.
     */
    public function isFileDigest(): bool
    {
        return $this->clientOperations === [];
    }

    /**
     * Whether the submitted digest is the root of a merkle tree binding several files.
     */
    public function isBatchRoot(): bool
    {
        return \count($this->clientOperations) > ($this->nonce === null ? 0 : self::NONCE_STEP_LENGTH);
    }
}
