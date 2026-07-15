<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Exception;

use InvalidArgumentException;

/**
 * Thrown when caller-supplied input is invalid: an unreadable file, an empty
 * digest, an out-of-range value, and so on.
 */
final class InvalidInputException extends InvalidArgumentException implements ElephStampException {}
