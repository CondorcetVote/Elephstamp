<?php

declare(strict_types=1);

namespace Tests\Support;

use CondorcetVote\ElephStamp\Calendar\CalendarClient;

/**
 * Forwards to another calendar client and records every upgrade poll, so a
 * test can assert how many requests a pass really made.
 */
final class CountingCalendarClient implements CalendarClient
{
    /**
     * @var list<list<array{url: string, commitment: string}>> the requests of each getTimestamps() call
     */
    public array $polls = [];

    public function __construct(public readonly CalendarClient $inner) {}

    public function submit(array $calendarUrls, string $digest): array
    {
        return $this->inner->submit($calendarUrls, $digest);
    }

    public function getTimestamps(array $requests): array
    {
        $this->polls[] = $requests;

        return $this->inner->getTimestamps($requests);
    }

    /**
     * Every (calendar, commitment) pair requested so far, in order.
     *
     * @return list<array{url: string, commitment: string}>
     */
    public function requests(): array
    {
        return array_merge(...$this->polls, ...[[]]);
    }
}
