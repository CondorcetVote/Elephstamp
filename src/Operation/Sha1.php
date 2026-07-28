<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

/**
 * SHA-1.
 *
 * Collision-prone hashes are still secure for timestamping: a collision only
 * proves two messages existed before a point in time, which is exactly the
 * claim a timestamp makes.
 */
final class Sha1 extends HashOperation
{
    public const TAG = "\x02";

    public function tag(): string
    {
        return self::TAG;
    }

    public function describe(): string
    {
        return 'sha1';
    }

    public function digestLength(): int
    {
        return 20;
    }

    protected function algorithm(): string
    {
        return 'sha1';
    }
}
