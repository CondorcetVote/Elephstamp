<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Exception;

use RuntimeException;

/**
 * Thrown when a calendar server cannot be reached or returns an unexpected
 * response (network error, non-200/404 status, oversized body, ...).
 */
class CalendarException extends RuntimeException implements ElephStampException {}
