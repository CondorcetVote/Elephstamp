<?php

declare(strict_types=1);

namespace Tests\Support;

use CondorcetVote\ElephStamp\Verify\{BlockHeader, BlockHeaderSource};

/**
 * Forwards to another block header source and counts what is asked of it.
 */
final class CountingBlockHeaderSource implements BlockHeaderSource
{
    public int $tipRequests = 0;

    /**
     * @var list<int> the height of every header request, in order
     */
    public array $headerRequests = [];

    public function __construct(public readonly BlockHeaderSource $inner) {}

    public function blockHeader(int $height): BlockHeader
    {
        $this->headerRequests[] = $height;

        return $this->inner->blockHeader($height);
    }

    public function tipHeight(): int
    {
        ++$this->tipRequests;

        return $this->inner->tipHeight();
    }

    public function describe(): string
    {
        return $this->inner->describe();
    }
}
