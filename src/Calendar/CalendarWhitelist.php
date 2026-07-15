<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Calendar;

/**
 * Allowlist deciding which calendar URIs {@see \CondorcetVote\ElephStamp\ElephStamp::upgrade()}
 * is permitted to contact.
 *
 * An `.ots` proof carries the calendar URIs to poll inside its pending
 * attestations. Since a proof may come from an untrusted source, contacting
 * those URIs blindly would let an attacker point the process at arbitrary hosts
 * (an SSRF vector). This whitelist restricts upgrades to hosts you trust.
 *
 * Patterns are `scheme://host` URLs whose host may contain shell-style globs,
 * e.g. `https://*.calendar.opentimestamps.org`. Query strings, fragments and
 * userinfo are never allowed in a matched URL.
 */
final class CalendarWhitelist
{
    /**
     * @param list<string> $patterns
     */
    public function __construct(private readonly array $patterns) {}

    public function allows(string $url): bool
    {
        $target = parse_url($url);

        // Reject unparseable URLs, and any URL carrying a query, fragment or
        // credentials (they have no place in a calendar URI and only widen the
        // attack surface).
        if ($target === false || isset($target['query']) || isset($target['fragment']) || isset($target['user'])) {
            return false;
        }

        $scheme = $target['scheme'] ?? '';
        $host = $target['host'] ?? '';
        $path = rtrim($target['path'] ?? '', '/');

        foreach ($this->patterns as $pattern) {
            $allowed = parse_url($pattern);

            if ($allowed === false) {
                continue;
            }

            if (
                $scheme === ($allowed['scheme'] ?? '')
                && $path === rtrim($allowed['path'] ?? '', '/')
                && fnmatch($allowed['host'] ?? '', $host)
            ) {
                return true;
            }
        }

        return false;
    }
}
