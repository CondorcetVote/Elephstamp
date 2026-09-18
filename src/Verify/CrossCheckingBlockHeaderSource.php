<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use CondorcetVote\ElephStamp\Exception\{BlockSourceException, InvalidInputException};

/**
 * Asks several sources and only answers when they all agree.
 *
 * This bounds the trust placed in any single explorer: a header is accepted
 * only if every source returns the same hash, merkle root and time for the
 * height. The chain tip is the lowest one reported, so confirmations are
 * never overstated.
 */
final class CrossCheckingBlockHeaderSource implements BlockHeaderSource
{
    /**
     * @var list<BlockHeaderSource>
     */
    private readonly array $sources;

    public function __construct(BlockHeaderSource ...$sources)
    {
        if (\count($sources) < 2) {
            throw new InvalidInputException('Cross-checking needs at least two sources');
        }

        $this->sources = array_values($sources);
    }

    public function blockHeader(int $height): BlockHeader
    {
        $reference = null;

        foreach ($this->sources as $source) {
            $header = $source->blockHeader($height);

            if ($reference === null) {
                $reference = $header;

                continue;
            }

            if (!hash_equals($reference->hash, $header->hash) || !hash_equals($reference->merkleRoot, $header->merkleRoot) || $reference->time != $header->time) {
                throw new BlockSourceException(\sprintf(
                    'Sources disagree about block %d: %s says %s, %s says %s',
                    $height,
                    $this->sources[0]->describe(),
                    $reference->hashHex(),
                    $source->describe(),
                    $header->hashHex(),
                ));
            }
        }

        return $reference;
    }

    public function tipHeight(): int
    {
        return min(array_map(static fn(BlockHeaderSource $source): int => $source->tipHeight(), $this->sources));
    }

    public function describe(): string
    {
        $names = array_map(static fn(BlockHeaderSource $source): string => $source->describe(), $this->sources);
        $last = array_pop($names);

        return implode(', ', $names) . ' and ' . $last . ' (cross-checked)';
    }
}
