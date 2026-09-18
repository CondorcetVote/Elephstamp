<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console;

use CondorcetVote\ElephStamp\Calendar\HttpCalendarClient;
use CondorcetVote\ElephStamp\ElephStamp;

/**
 * The production factory: talks to real calendars over HTTPS.
 */
final class HttpClientFactory implements ClientFactory
{
    public function create(ClientOptions $options): ElephStamp
    {
        $calendarClient = $options->timeout === null
            ? new HttpCalendarClient(userAgent: 'ElephStamp CLI')
            : new HttpCalendarClient(userAgent: 'ElephStamp CLI', timeout: $options->timeout, maxDuration: $options->timeout);

        return new ElephStamp(
            calendarClient: $calendarClient,
            calendarUrls: $options->resolvedCalendarUrls(),
            requiredCalendars: $options->requiredCalendars,
            upgradeWhitelist: $options->resolvedWhitelist(),
        );
    }
}
