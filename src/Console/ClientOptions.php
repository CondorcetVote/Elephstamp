<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console;

use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Operation\HashOperation;
use CondorcetVote\ElephStamp\Verify\Explorer;
use SensitiveParameter;

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
     * @param float|null     $timeout           seconds to wait for a calendar or explorer before giving up; null for the library default
     * @param list<Explorer> $explorers         block explorers to verify against; all of them must agree when several are given
     * @param list<string>   $explorerUrls      base URLs of additional Esplora-compatible explorers, e.g. a self-hosted one
     * @param string|null    $node              JSON-RPC URL of a Bitcoin node to verify against, alone or alongside the explorers
     * @param string|null    $nodeUser          RPC user for $node, with $nodePassword
     * @param string|null    $nodePassword      RPC password for $node, with $nodeUser
     * @param string|null    $nodeCookieFile    Bitcoin Core `.cookie` file holding the RPC credentials, instead of a user and password
     * @param HashOperation|null $hashOperation the hash a stamp commits to a file with; null for the library default, SHA-256
     */
    public function __construct(
        public readonly array $calendarUrls = [],
        public readonly ?int $requiredCalendars = null,
        public readonly array $whitelist = [],
        public readonly bool $useDefaultWhitelist = true,
        public readonly ?float $timeout = null,
        public readonly array $explorers = [],
        public readonly array $explorerUrls = [],
        #[SensitiveParameter]
        public readonly ?string $node = null,
        public readonly ?string $nodeUser = null,
        #[SensitiveParameter]
        public readonly ?string $nodePassword = null,
        public readonly ?string $nodeCookieFile = null,
        public readonly ?HashOperation $hashOperation = null,
    ) {}

    /**
     * Resolve the explorer names given on the command line.
     *
     * @param list<string> $names
     *
     * @throws InvalidInputException on an unknown name
     *
     * @return list<Explorer>
     */
    public static function explorersFromNames(array $names): array
    {
        return array_map(
            static fn(string $name): Explorer => Explorer::tryFrom($name) ?? throw new InvalidInputException(\sprintf('Unknown explorer "%s"; known: %s', $name, implode(', ', array_column(Explorer::cases(), 'value')))),
            $names,
        );
    }

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
     * The explorers verification consults: the chosen ones, or the default
     * when none was chosen and neither a custom URL nor a node was given.
     *
     * @return list<Explorer>
     */
    public function resolvedExplorers(): array
    {
        if ($this->explorers === [] && $this->explorerUrls === [] && $this->node === null) {
            return [Explorer::DEFAULT];
        }

        return array_values(array_unique($this->explorers, \SORT_REGULAR));
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
