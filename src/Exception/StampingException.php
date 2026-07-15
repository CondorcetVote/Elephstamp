<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Exception;

use RuntimeException;

/**
 * Thrown when a stamp request cannot gather enough calendar attestations to
 * satisfy the required threshold (the m-of-n policy).
 */
final class StampingException extends RuntimeException implements ElephStampException {}
