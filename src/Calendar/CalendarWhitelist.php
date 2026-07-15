<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Calendar;

use CondorcetVote\ElephStamp\Exception\InvalidInputException;

/**
 * Allowlist deciding which calendar URIs {@see \CondorcetVote\ElephStamp\ElephStamp::upgrade()}
 * is permitted to contact.
 *
 * An `.ots` proof carries the calendar URIs to poll inside its pending
 * attestations. Since a proof may come from an untrusted source, contacting
 * those URIs blindly would let an attacker point the process at arbitrary hosts
 * (an SSRF vector). This whitelist restricts upgrades to hosts you trust.
 *
 * Patterns are `https://host` URLs whose host may contain shell-style globs,
 * e.g. `https://*.calendar.opentimestamps.org`. Only https is accepted: a
 * plaintext calendar connection would let a network attacker inject forged
 * proofs. Query strings, fragments and userinfo are never allowed in a
 * matched URL.
 */
final class CalendarWhitelist
{
    /**
     * @param list<string> $patterns
     *
     * @throws InvalidInputException on a non-https pattern
     */
    public function __construct(private readonly array $patterns)
    {
        foreach ($patterns as $pattern) {
            if (!str_starts_with($pattern, 'https://')) {
                throw new InvalidInputException(\sprintf('Whitelist patterns must use https, got: %s', $pattern));
            }
        }
    }

    public function allows(string $url): bool
    {
        $target = parse_url($url);

        // Reject unparseable URLs, and any URL carrying a query, fragment or
        // credentials (they have no place in a calendar URI and only widen the
        // attack surface).
        if ($target === false || isset($target['query']) || isset($target['fragment']) || isset($target['user'])) {
            return false;
        }

        // Scheme and host are case-insensitive (RFC 3986); a legitimate proof
        // with unusual casing must not become silently un-upgradable.
        $scheme = strtolower($target['scheme'] ?? '');
        $host = strtolower($target['host'] ?? '');
        $path = rtrim($target['path'] ?? '', '/');

        foreach ($this->patterns as $pattern) {
            $allowed = parse_url($pattern);

            if ($allowed === false) {
                continue;
            }

            if (
                $scheme === strtolower($allowed['scheme'] ?? '')
                // An explicit port must be whitelisted explicitly: without
                // this, a hostile proof could target any port of a trusted
                // host.
                && ($target['port'] ?? null) === ($allowed['port'] ?? null)
                && $path === rtrim($allowed['path'] ?? '', '/')
                && fnmatch(strtolower($allowed['host'] ?? ''), $host)
            ) {
                return true;
            }
        }

        return false;
    }
}
