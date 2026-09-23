<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Calendar\CalendarWhitelist;

it('allows hosts matching a glob pattern', function (): void {
    $whitelist = new CalendarWhitelist(['https://*.calendar.opentimestamps.org']);

    expect($whitelist->allows('https://alice.btc.calendar.opentimestamps.org'))->toBeTrue()
        ->and($whitelist->allows('https://finney.calendar.eternitywall.com'))->toBeFalse();
});

it('rejects a different scheme', function (): void {
    $whitelist = new CalendarWhitelist(['https://*.calendar.opentimestamps.org']);

    expect($whitelist->allows('http://alice.btc.calendar.opentimestamps.org'))->toBeFalse();
});

it('rejects URLs carrying a query, fragment or credentials', function (): void {
    $whitelist = new CalendarWhitelist(['https://a.example']);

    expect($whitelist->allows('https://a.example?x=1'))->toBeFalse()
        ->and($whitelist->allows('https://a.example#frag'))->toBeFalse()
        ->and($whitelist->allows('https://user@a.example'))->toBeFalse();
});

it('ignores a trailing slash', function (): void {
    $whitelist = new CalendarWhitelist(['https://a.example']);

    expect($whitelist->allows('https://a.example/'))->toBeTrue();
});

it('compares scheme and host case-insensitively', function (): void {
    $whitelist = new CalendarWhitelist(['https://*.calendar.opentimestamps.org']);

    expect($whitelist->allows('HTTPS://ALICE.BTC.CALENDAR.OPENTIMESTAMPS.ORG'))->toBeTrue();
});

it('rejects an explicit port unless the pattern whitelists it', function (): void {
    $whitelist = new CalendarWhitelist(['https://*.calendar.opentimestamps.org', 'https://private.example:8443']);

    expect($whitelist->allows('https://alice.btc.calendar.opentimestamps.org:8443'))->toBeFalse()
        ->and($whitelist->allows('https://private.example:8443'))->toBeTrue()
        ->and($whitelist->allows('https://private.example'))->toBeFalse()
        ->and($whitelist->allows('https://private.example:9000'))->toBeFalse();
});

it('rejects non-https whitelist patterns at construction', function (): void {
    new CalendarWhitelist(['http://a.example']);
})->throws(CondorcetVote\ElephStamp\Exception\InvalidInputException::class, 'https');

it('refuses a glob that is not anchored to a literal domain', function (string $pattern): void {
    new CalendarWhitelist([$pattern]);
})->with([
    'any host' => 'https://*',
    'any dotted host' => 'https://*.*',
    'a top-level domain' => 'https://*.example',
    'a glob in the domain' => 'https://*.calendar*.org',
    'a glob in the top-level domain' => 'https://*.calendar.*',
    'a prefix glob' => 'https://*example.org',
    'an IPv4 range' => 'https://10.0.0.*',
    'an IPv4 range ending with literal octets' => 'https://*.0.0.1',
    'a glob without any dot' => 'https://1*',
])->throws(CondorcetVote\ElephStamp\Exception\InvalidInputException::class, 'too broad');

it('refuses a pattern without a host', function (): void {
    new CalendarWhitelist(['https://']);
})->throws(CondorcetVote\ElephStamp\Exception\InvalidInputException::class, 'not a valid https URL');

it('accepts globs anchored to a domain and literal hosts of any kind', function (): void {
    $whitelist = new CalendarWhitelist(['https://*.internal.example', 'https://ots-*.calendar.example.org', 'https://10.0.0.5', 'https://localhost']);

    expect($whitelist->allows('https://a.internal.example'))->toBeTrue()
        ->and($whitelist->allows('https://internal.example'))->toBeFalse()
        ->and($whitelist->allows('https://ots-1.calendar.example.org'))->toBeTrue()
        ->and($whitelist->allows('https://10.0.0.5'))->toBeTrue()
        ->and($whitelist->allows('https://10.0.0.6'))->toBeFalse()
        ->and($whitelist->allows('https://localhost'))->toBeTrue();
});

it('refuses a pattern that is not a strict RFC 3986 URL', function (string $pattern): void {
    new CalendarWhitelist([$pattern]);
})->with([
    'a backslash' => 'https://evil.example\\@trusted.example',
    'a non-ASCII host' => 'https://été.example',
    'a query' => 'https://a.example?x=1',
    'credentials' => 'https://user@a.example',
])->throws(CondorcetVote\ElephStamp\Exception\InvalidInputException::class, 'not a valid https URL');

it('rejects a URL the strict parser cannot read, even when a lenient one would see a trusted host', function (): void {
    $whitelist = new CalendarWhitelist(['https://trusted.example']);

    // parse_url() reads the host as trusted.example; browsers and WHATWG as evil.example.
    expect($whitelist->allows('https://evil.example\\@trusted.example'))->toBeFalse()
        ->and($whitelist->resolve('https://evil.example\\@trusted.example'))->toBeNull();
});

it('resolves an allowed URL to its normalized form', function (): void {
    $whitelist = new CalendarWhitelist(['https://*.calendar.opentimestamps.org', 'https://private.example/ots']);

    expect($whitelist->resolve('HTTPS://Alice.BTC.Calendar.OpenTimestamps.org/'))->toBe('https://alice.btc.calendar.opentimestamps.org')
        ->and($whitelist->resolve('https://alice.btc.calendar.opentimestamps.org:443'))->toBe('https://alice.btc.calendar.opentimestamps.org')
        ->and($whitelist->resolve('https://private%2Eexample/x/../ots/'))->toBe('https://private.example/ots')
        ->and($whitelist->resolve('https://private.example/other'))->toBeNull();
});

it('matches a literal IPv6 host exactly', function (): void {
    $whitelist = new CalendarWhitelist(['https://[::1]:8443']);

    expect($whitelist->resolve('https://[::1]:8443'))->toBe('https://[::1]:8443')
        ->and($whitelist->allows('https://[::2]:8443'))->toBeFalse();
});
