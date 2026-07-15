<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Calendar;

use CondorcetVote\ElephStamp\Exception\ElephStampException;
use CondorcetVote\ElephStamp\Timestamp;

/**
 * The outcome of contacting a single calendar.
 *
 * Exactly one of three states holds: success (a timestamp was returned),
 * not-found (the calendar has no attestation yet — a normal pending result of
 * an upgrade), or failure (the calendar was unreachable or misbehaved). A
 * failure never aborts a batch: the caller decides what to do with it.
 */
final class CalendarResponse
{
    private function __construct(
        public readonly string $calendarUrl,
        public readonly ?Timestamp $timestamp,
        public readonly ?ElephStampException $error,
        public readonly bool $notFound,
    ) {}

    public static function success(string $calendarUrl, Timestamp $timestamp): self
    {
        return new self($calendarUrl, $timestamp, null, false);
    }

    public static function notFound(string $calendarUrl): self
    {
        return new self($calendarUrl, null, null, true);
    }

    public static function failure(string $calendarUrl, ElephStampException $error): self
    {
        return new self($calendarUrl, null, $error, false);
    }

    public function isSuccess(): bool
    {
        return $this->timestamp !== null;
    }

    public function isNotFound(): bool
    {
        return $this->notFound;
    }

    public function isFailure(): bool
    {
        return $this->error !== null;
    }
}
