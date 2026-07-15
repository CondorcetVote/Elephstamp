<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Calendar;

/**
 * Talks to OpenTimestamps calendar servers.
 *
 * Both methods are batch operations: the caller hands over every calendar at
 * once and gets one {@see CalendarResponse} per request, in the same order.
 * Implementations are expected to contact the calendars concurrently and to
 * never throw for a single calendar's failure — a failure is reported as a
 * {@see CalendarResponse::failure()} entry instead.
 *
 * This is the seam the library fakes in tests: the real implementation
 * ({@see HttpCalendarClient}) speaks HTTP concurrently, while
 * {@see FakeCalendarClient} serves deterministic responses from memory.
 */
interface CalendarClient
{
    /**
     * Submit one digest to several calendars.
     *
     * @param list<string> $calendarUrls
     *
     * @return list<CalendarResponse> one entry per URL, in the same order
     */
    public function submit(array $calendarUrls, string $digest): array;

    /**
     * Fetch upgraded timestamps for several (calendar, commitment) pairs.
     *
     * A calendar with no attestation yet yields {@see CalendarResponse::notFound()}.
     *
     * @param list<array{url: string, commitment: string}> $requests
     *
     * @return list<CalendarResponse> one entry per request, in the same order
     */
    public function getTimestamps(array $requests): array;
}
