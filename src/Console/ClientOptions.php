<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console;

use CondorcetVote\ElephStamp\ElephStamp;

/**
 * Calendar-related settings collected from the command line.
 *
 * Every field is optional: an empty or null value means "use the library
 * default", so the same object serves both stamping and upgrading commands.
 */
final class ClientOptions
{
    /**
     * @param list<string> $calendarUrls        calendars to submit to; empty for {@see ElephStamp::DEFAULT_CALENDAR_URLS}
     * @param int|null     $requiredCalendars   the "m" of the m-of-n stamping policy; null for the library default
     * @param list<string> $whitelist           extra host patterns an upgrade may contact
     * @param bool         $useDefaultWhitelist whether {@see ElephStamp::DEFAULT_UPGRADE_WHITELIST} is kept alongside $whitelist
     * @param float|null   $timeout             seconds to wait for a calendar before giving up; null for the library default
     */
    public function __construct(
        public readonly array $calendarUrls = [],
        public readonly ?int $requiredCalendars = null,
        public readonly array $whitelist = [],
        public readonly bool $useDefaultWhitelist = true,
        public readonly ?float $timeout = null,
    ) {}

    /**
     * The calendars a stamp is submitted to.
     *
     * @return list<string>
     */
    public function resolvedCalendarUrls(): array
    {
        return $this->calendarUrls === [] ? ElephStamp::DEFAULT_CALENDAR_URLS : $this->calendarUrls;
    }

    /**
     * The host patterns an upgrade may contact.
     *
     * @return list<string>
     */
    public function resolvedWhitelist(): array
    {
        $patterns = $this->useDefaultWhitelist ? ElephStamp::DEFAULT_UPGRADE_WHITELIST : [];

        return array_values(array_unique([...$patterns, ...$this->whitelist]));
    }
}
