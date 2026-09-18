<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Exception;

use RuntimeException;

/**
 * Thrown when a block header source (block explorer, node) cannot deliver a
 * header: unreachable, unknown block, malformed or inconsistent answer.
 */
final class BlockSourceException extends RuntimeException implements ElephStampException {}
