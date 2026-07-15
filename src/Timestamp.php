<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation, TimeAttestation};
use CondorcetVote\ElephStamp\Serialization\{Deserializer, Serializer};
use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Operation\Operation;

/**
 * A proof that one or more attestations commit to a message.
 *
 * The proof is a tree: each node is a message, each edge an {@see Operation}
 * acting on it, and the leaves are {@see TimeAttestation}s. This class is the
 * mutable heart of the format — merging calendar responses grows the tree.
 */
final class Timestamp
{
    private const int RECURSION_LIMIT = 256;

    /**
     * @var array<string, TimeAttestation> keyed by attestation identity
     */
    private array $attestations = [];

    /**
     * @var array<string, array{op: Operation, timestamp: Timestamp}> keyed by operation identity
     */
    private array $ops = [];

    public function __construct(public readonly string $msg)
    {
        if (\strlen($msg) > Operation::MAX_MSG_LENGTH) {
            throw new SerializationException(\sprintf('Message exceeds operation length limit: %d > %d', \strlen($msg), Operation::MAX_MSG_LENGTH));
        }
    }

    /**
     * Attach an attestation to this node (deduplicated).
     */
    public function addAttestation(TimeAttestation $attestation): void
    {
        $this->attestations[$attestation->identityKey()] = $attestation;
    }

    /**
     * Add an operation edge, returning the timestamp of its result.
     *
     * If the operation is already present its existing child timestamp is
     * returned, so the tree stays a proper DAG.
     */
    public function addOp(Operation $operation): self
    {
        $key = $operation->comparisonKey();

        if (isset($this->ops[$key])) {
            return $this->ops[$key]['timestamp'];
        }

        $child = new self($operation->apply($this->msg));
        $this->ops[$key] = ['op' => $operation, 'timestamp' => $child];

        return $child;
    }

    /**
     * Bind a specific child timestamp to an operation edge.
     *
     * @throws SerializationException if the child is for a different message
     */
    public function setOp(Operation $operation, self $child): void
    {
        if ($operation->apply($this->msg) !== $child->msg) {
            throw new SerializationException("Operation result does not match the child timestamp's message");
        }

        $this->ops[$operation->comparisonKey()] = ['op' => $operation, 'timestamp' => $child];
    }

    /**
     * @return list<TimeAttestation>
     */
    public function attestations(): array
    {
        return array_values($this->attestations);
    }

    /**
     * @return list<array{op: Operation, timestamp: Timestamp}>
     */
    public function operations(): array
    {
        return array_values($this->ops);
    }

    /**
     * Merge every operation and attestation from another timestamp into this one.
     *
     * @throws SerializationException if the timestamps are for different messages
     */
    public function merge(self $other): void
    {
        if ($this->msg !== $other->msg) {
            throw new SerializationException('Cannot merge timestamps for different messages');
        }

        foreach ($other->attestations as $attestation) {
            $this->addAttestation($attestation);
        }

        foreach ($other->ops as ['op' => $op, 'timestamp' => $otherChild]) {
            $this->addOp($op)->merge($otherChild);
        }
    }

    /**
     * Every attestation in the tree, paired with the message it commits to.
     *
     * @return list<array{msg: string, attestation: TimeAttestation}>
     */
    public function allAttestations(): array
    {
        $result = [];

        foreach ($this->attestations as $attestation) {
            $result[] = ['msg' => $this->msg, 'attestation' => $attestation];
        }

        foreach ($this->ops as ['timestamp' => $child]) {
            foreach ($child->allAttestations() as $entry) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    /**
     * Whether the tree contains a Bitcoin attestation, i.e. is complete.
     */
    public function hasBitcoinAttestation(): bool
    {
        foreach ($this->allAttestations() as ['attestation' => $attestation]) {
            if ($attestation instanceof BitcoinAttestation) {
                return true;
            }
        }

        return false;
    }

    /**
     * The shallowest nodes that carry a pending attestation.
     *
     * These are the nodes whose message must be re-submitted to a calendar to
     * upgrade the timestamp. A node that already carries any attestation stops
     * the descent, mirroring the reference client.
     *
     * @return list<array{node: Timestamp, attestation: PendingAttestation}>
     */
    public function findPending(): array
    {
        $result = [];

        foreach ($this->directlyVerified() as $node) {
            foreach ($node->attestations as $attestation) {
                if ($attestation instanceof PendingAttestation) {
                    $result[] = ['node' => $node, 'attestation' => $attestation];
                }
            }
        }

        return $result;
    }

    /**
     * Render the proof tree as an indented, human-readable string.
     *
     * Intended for inspection and debugging, not for parsing.
     */
    public function describe(int $indent = 0): string
    {
        $pad = str_repeat(' ', $indent);
        $output = '';

        foreach ($this->sortedAttestations() as $attestation) {
            $output .= $pad . $attestation->describe() . "\n";
        }

        $ops = $this->sortedOps();

        if (\count($ops) > 1) {
            foreach ($ops as $entry) {
                $output .= $pad . ' -> ' . $entry['op']->describe() . "\n";
                $output .= $entry['timestamp']->describe($indent + 4);
            }
        } elseif (\count($ops) === 1) {
            $output .= $pad . $ops[0]['op']->describe() . "\n";
            $output .= $ops[0]['timestamp']->describe($indent);
        }

        return $output;
    }

    public function serialize(Serializer $serializer): void
    {
        if (empty($this->attestations) && empty($this->ops)) {
            throw new SerializationException('An empty timestamp cannot be serialized');
        }

        $attestations = $this->sortedAttestations();
        $lastAttestation = !empty($attestations) ? $attestations[\count($attestations) - 1] : null;

        // Every attestation but the highest-sorted one is written up front.
        for ($i = 0, $n = \count($attestations) - 1; $i < $n; ++$i) {
            $serializer->writeBytes("\xff\x00");
            $attestations[$i]->serialize($serializer);
        }

        if (empty($this->ops)) {
            $serializer->writeBytes("\x00");
            $lastAttestation?->serialize($serializer);

            return;
        }

        if ($lastAttestation !== null) {
            $serializer->writeBytes("\xff\x00");
            $lastAttestation->serialize($serializer);
        }

        $ops = $this->sortedOps();

        for ($i = 0, $n = \count($ops) - 1; $i < $n; ++$i) {
            $serializer->writeBytes("\xff");
            $ops[$i]['op']->serialize($serializer);
            $ops[$i]['timestamp']->serialize($serializer);
        }

        $last = $ops[\count($ops) - 1];
        $last['op']->serialize($serializer);
        $last['timestamp']->serialize($serializer);
    }

    /**
     * Deserialize a timestamp for a known initial message.
     *
     * The message is not stored in the format, so it must be supplied; it is
     * assumed correct and used to compute every operation result eagerly.
     */
    public static function deserialize(Deserializer $deserializer, string $initialMsg, int $recursionLimit = self::RECURSION_LIMIT): self
    {
        if ($recursionLimit <= 0) {
            throw new SerializationException('Reached timestamp recursion depth limit while deserializing');
        }

        $self = new self($initialMsg);

        $consume = static function (string $tag) use ($self, $deserializer, $initialMsg, $recursionLimit): void {
            if ($tag === "\x00") {
                $self->addAttestation(TimeAttestation::deserialize($deserializer));

                return;
            }

            $op = Operation::fromTag($tag, $deserializer);
            $child = self::deserialize($deserializer, $op->apply($initialMsg), $recursionLimit - 1);
            $self->setOp($op, $child);
        };

        $tag = $deserializer->readBytes(1);

        while ($tag === "\xff") {
            $consume($deserializer->readBytes(1));
            $tag = $deserializer->readBytes(1);
        }

        $consume($tag);

        return $self;
    }

    /**
     * @return list<Timestamp>
     */
    private function directlyVerified(): array
    {
        if (!empty($this->attestations)) {
            return [$this];
        }

        $result = [];

        foreach ($this->ops as ['timestamp' => $child]) {
            foreach ($child->directlyVerified() as $node) {
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * @return list<TimeAttestation>
     */
    private function sortedAttestations(): array
    {
        $attestations = array_values($this->attestations);
        usort($attestations, static fn(TimeAttestation $a, TimeAttestation $b): int => $a->compareTo($b));

        return $attestations;
    }

    /**
     * @return list<array{op: Operation, timestamp: Timestamp}>
     */
    private function sortedOps(): array
    {
        $ops = array_values($this->ops);
        usort($ops, static fn(array $a, array $b): int => strcmp($a['op']->comparisonKey(), $b['op']->comparisonKey()));

        return $ops;
    }
}
