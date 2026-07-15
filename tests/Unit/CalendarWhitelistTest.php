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
