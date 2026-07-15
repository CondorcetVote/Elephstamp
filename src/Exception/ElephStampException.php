<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Exception;

use Throwable;

/**
 * Marker interface implemented by every exception thrown by this library.
 *
 * Catch this to handle any ElephStamp failure regardless of its concrete type.
 */
interface ElephStampException extends Throwable {}
