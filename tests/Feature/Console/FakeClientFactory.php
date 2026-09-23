<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use CondorcetVote\ElephStamp\Calendar\{CalendarClient, FakeCalendarClient};
use CondorcetVote\ElephStamp\Console\{ClientFactory, ClientOptions};
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\Random\DeterministicRandomSource;
use CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource;

/**
 * Builds offline clients for the CLI tests and records the options it was given.
 */
final class FakeClientFactory implements ClientFactory
{
    public ?ClientOptions $lastOptions = null;

    public readonly FakeBlockHeaderSource $blocks;

    /**
     * @param FakeBlockHeaderSource|null $blocks the chain to verify against; by default the one a fake calendar mines into
     */
    public function __construct(
        public readonly CalendarClient $calendar = new FakeCalendarClient,
        public readonly string $calendarUrl = ElephStamp::FAKE_CALENDAR_URL,
        ?FakeBlockHeaderSource $blocks = null,
    ) {
        $this->blocks = $blocks ?? ($calendar instanceof FakeCalendarClient ? $calendar->blocks() : new FakeBlockHeaderSource);
    }

    public function create(ClientOptions $options): ElephStamp
    {
        $this->lastOptions = $options;

        // The calendar this factory stamps with is always upgradable, as in
        // production; --no-default-whitelist only drops the public operators.
        return new ElephStamp(
            calendarClient: $this->calendar,
            calendarUrls: [$this->calendarUrl],
            randomSource: new DeterministicRandomSource,
            upgradeWhitelist: $options->resolvedWhitelist(),
            blockHeaderSource: $this->blocks,
        );
    }
}
