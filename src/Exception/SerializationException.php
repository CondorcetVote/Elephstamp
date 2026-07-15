<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Exception;

use RuntimeException;

/**
 * Thrown when reading or writing the OpenTimestamps binary format fails.
 *
 * Covers bad magic bytes, truncated data, trailing garbage, unsupported
 * versions, unknown operation tags and recursion-limit violations.
 */
final class SerializationException extends RuntimeException implements ElephStampException {}
