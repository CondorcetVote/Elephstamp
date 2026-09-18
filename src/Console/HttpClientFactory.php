<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console;

use CondorcetVote\ElephStamp\Calendar\HttpCalendarClient;
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\Verify\{BlockHeaderSource, CrossCheckingBlockHeaderSource, EsploraBlockHeaderSource, Explorer};

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
            blockHeaderSource: self::blockHeaderSource($options),
        );
    }

    /**
     * One explorer, or several combined so that they must all agree.
     */
    private static function blockHeaderSource(ClientOptions $options): BlockHeaderSource
    {
        $sources = array_map(
            static fn(Explorer $explorer): EsploraBlockHeaderSource => $explorer->source(timeout: $options->timeout),
            $options->resolvedExplorers(),
        );

        foreach ($options->explorerUrls as $url) {
            $sources[] = $options->timeout === null
                ? new EsploraBlockHeaderSource($url, userAgent: 'ElephStamp CLI')
                : new EsploraBlockHeaderSource($url, userAgent: 'ElephStamp CLI', timeout: $options->timeout, maxDuration: $options->timeout);
        }

        return \count($sources) === 1 ? $sources[0] : new CrossCheckingBlockHeaderSource(...$sources);
    }
}
