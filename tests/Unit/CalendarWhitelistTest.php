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
    $whitelist = new CalendarWhitelist(['https://*.example']);

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
