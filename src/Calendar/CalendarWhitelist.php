<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Calendar;

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use Uri\Rfc3986\Uri;

/**
 * Allowlist deciding which calendar URIs {@see \CondorcetVote\ElephStamp\ElephStamp::upgrade()}
 * is permitted to contact.
 *
 * An `.ots` proof carries the calendar URIs to poll inside its pending
 * attestations. Since a proof may come from an untrusted source, contacting
 * those URIs blindly would let an attacker point the process at arbitrary hosts
 * (an SSRF vector). This whitelist restricts upgrades to hosts you trust.
 *
 * Patterns are `https://host` URLs whose host may contain `*` globs,
 * e.g. `https://*.calendar.opentimestamps.org`. A glob must be followed by at
 * least two literal domain labels, the last one not numeric: `https://*`,
 * `https://*.example` or `https://10.0.0.*` would reach any host, a whole
 * top-level domain or a private network. A pattern without a glob is taken
 * as an explicit choice and may name any host, IP addresses included. Only
 * https is accepted: a plaintext calendar connection would let a network
 * attacker inject forged proofs. Query strings, fragments and userinfo are
 * never allowed in a matched URL.
 *
 * URLs are parsed strictly as RFC 3986 and compared in their normalized form
 * (lowercase host, decoded unreserved characters, no dot segments, no default
 * port). {@see resolve()} returns that normalized form, so the URL checked is
 * exactly the URL contacted.
 */
final class CalendarWhitelist
{
    private const int HTTPS_PORT = 443;

    /**
     * @var list<array{host: string, port: int|null, path: string}>
     */
    private readonly array $rules;

    /**
     * @param list<string> $patterns
     *
     * @throws InvalidInputException on a pattern that is not an https URL with a host, or whose glob is too broad to be anchored to a domain
     */
    public function __construct(array $patterns)
    {
        $rules = [];

        foreach ($patterns as $pattern) {
            if (!str_starts_with(strtolower($pattern), 'https://')) {
                throw new InvalidInputException(\sprintf('Whitelist patterns must use https, got: %s', $pattern));
            }

            $rule = self::parse($pattern);

            if ($rule === null) {
                throw new InvalidInputException(\sprintf('Whitelist pattern is not a valid https URL with a host (and no query, fragment or credentials): %s', $pattern));
            }

            if (self::hasGlob($rule['host']) && !self::isAnchored($rule['host'])) {
                throw new InvalidInputException(\sprintf(
                    'Whitelist pattern is too broad: %s; a glob must be followed by at least two literal domain labels, e.g. https://*.calendar.example.org',
                    $pattern,
                ));
            }

            $rules[] = $rule;
        }

        $this->rules = $rules;
    }

    public function allows(string $url): bool
    {
        return $this->resolve($url) !== null;
    }

    /**
     * The normalized URL to contact for $url, or null when it is not allowed.
     *
     * Send requests to this URL rather than to $url itself: it is the form
     * the whitelist actually checked, so no difference between two URL
     * parsers can make the request reach another host.
     */
    public function resolve(string $url): ?string
    {
        $target = self::parse($url);

        if ($target === null) {
            return null;
        }

        foreach ($this->rules as $rule) {
            if (
                // An explicit port must be whitelisted explicitly: without
                // this, a hostile proof could target any port of a trusted
                // host.
                $target['port'] === $rule['port']
                && $target['path'] === $rule['path']
                && self::hostMatches($rule['host'], $target['host'])
            ) {
                return 'https://' . $target['host'] . ($target['port'] === null ? '' : ':' . $target['port']) . $target['path'];
            }
        }

        return null;
    }

    /**
     * The normalized parts of an https URL, or null for anything unparseable,
     * not https, without a host, or carrying a query, fragment or credentials
     * (they have no place in a calendar URI and only widen the attack surface).
     *
     * @return array{host: string, port: int|null, path: string}|null
     */
    private static function parse(string $url): ?array
    {
        $uri = Uri::parse($url);

        if (
            $uri === null
            || $uri->getScheme() !== 'https'
            || $uri->getUserInfo() !== null
            || $uri->getQuery() !== null
            || $uri->getFragment() !== null
        ) {
            return null;
        }

        $host = $uri->getHost();

        if ($host === null || $host === '') {
            return null;
        }

        $port = $uri->getPort();

        return [
            'host' => $host,
            'port' => $port === self::HTTPS_PORT ? null : $port,
            'path' => rtrim($uri->getPath(), '/'),
        ];
    }

    private static function hostMatches(string $pattern, string $host): bool
    {
        // RFC 3986 allows no other glob character in a host, and a literal
        // host (an IPv6 one included) must not go through fnmatch().
        return self::hasGlob($pattern) ? fnmatch($pattern, $host, \FNM_NOESCAPE) : $pattern === $host;
    }

    private static function hasGlob(string $host): bool
    {
        return str_contains($host, '*');
    }

    /**
     * Whether a glob host ends with a literal domain of its own (`calendar.example.org`
     * in `*.calendar.example.org`), so it cannot match a whole top-level domain or
     * a range of IP addresses.
     */
    private static function isAnchored(string $host): bool
    {
        $labels = explode('.', $host);

        if (\count($labels) < 3) {
            return false;
        }

        [$domain, $topLevel] = \array_slice($labels, -2);

        return $domain !== '' && $topLevel !== ''
            && !self::hasGlob($domain) && !self::hasGlob($topLevel)
            && !ctype_digit($topLevel);
    }
}
