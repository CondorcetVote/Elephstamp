<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Operation;

/**
 * A hash operation computed by PHP's {@see hash()} extension.
 *
 * These hash incrementally, so a file of any size is digested in bounded
 * memory.
 */
abstract class NativeHashOperation extends HashOperation
{
    /**
     * Name of the algorithm as understood by PHP's {@see hash()} family.
     */
    abstract protected function algorithm(): string;

    final public function hashData(string $data): string
    {
        return hash($this->algorithm(), $data, binary: true);
    }

    final public function hashStream($stream): string
    {
        $context = hash_init($this->algorithm());
        hash_update_stream($context, $stream);

        return hash_final($context, binary: true);
    }

    final public function hashChunks(iterable $chunks): string
    {
        $context = hash_init($this->algorithm());

        foreach ($chunks as $chunk) {
            hash_update($context, $chunk);
        }

        return hash_final($context, binary: true);
    }
}
