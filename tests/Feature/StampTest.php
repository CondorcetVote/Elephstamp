<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Calendar\{CalendarClient, CalendarResponse, FakeCalendarClient};
use CondorcetVote\ElephStamp\Exception\{CalendarException, InvalidInputException, StampingException};
use CondorcetVote\ElephStamp\Random\DeterministicRandomSource;
use CondorcetVote\ElephStamp\{ElephStamp, FileToStamp, Receipt, Status, Timestamp};

it('stamps a file as pending and lists the calendar', function (): void {
    $receipt = ElephStamp::fake()->stamp(FileToStamp::fromContent('hello world'));

    expect($receipt->status())->toBe(Status::Pending)
        ->and($receipt->isPending())->toBeTrue()
        ->and($receipt->fileDigestHex())->toBe(hash('sha256', 'hello world'))
        ->and($receipt->pendingCalendarUris())->toBe([ElephStamp::FAKE_CALENDAR_URL]);
});

it('drives a receipt from pending to complete through the fake lifecycle', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('contract'));

    expect($client->upgrade($receipt))->toBeFalse()
        ->and($receipt->isPending())->toBeTrue();

    $client->fakeCalendar()->confirmAll(812_345);

    expect($client->upgrade($receipt))->toBeTrue()
        ->and($receipt->status())->toBe(Status::Complete)
        ->and($receipt->bitcoinBlockHeight())->toBe(812_345);
});

it('produces byte-identical proofs from a deterministic random source', function (): void {
    $bytesA = ElephStamp::fake()->stamp(FileToStamp::fromContent('same'))->toBytes();
    $bytesB = ElephStamp::fake()->stamp(FileToStamp::fromContent('same'))->toBytes();

    expect($bytesA)->toBe($bytesB);
});

it('commits directly to the plain digest when the nonce is disabled', function (): void {
    $receipt = ElephStamp::fake()->stamp(FileToStamp::fromContent('public data')->withoutNonce());

    // With no nonce the commitment is the file's own SHA-256, so the two proofs
    // for the same content are byte-identical regardless of randomness.
    $other = ElephStamp::fake()->stamp(FileToStamp::fromContent('public data')->withoutNonce());

    expect($receipt->fileDigestHex())->toBe(hash('sha256', 'public data'))
        ->and($receipt->toBytes())->toBe($other->toBytes());
});

it('stamps many files under one submission, each with its own receipt', function (): void {
    $receipts = ElephStamp::fake()->stampMany(
        FileToStamp::fromContent('file A'),
        FileToStamp::fromContent('file B'),
        FileToStamp::fromContent('file C'),
    );

    expect($receipts)->toHaveCount(3);

    foreach ($receipts as $index => $receipt) {
        expect($receipt->fileDigestHex())->toBe(hash('sha256', ['file A', 'file B', 'file C'][$index]))
            ->and(Receipt::fromBytes($receipt->toBytes())->fileDigestHex())->toBe($receipt->fileDigestHex());
    }
});

it('confirms a specific receipt through the fake calendar', function (): void {
    $calendar = new FakeCalendarClient;
    $client = ElephStamp::fake($calendar);

    // Two receipts submitted; confirming only the first must leave the second pending.
    $first = $client->stamp(FileToStamp::fromContent('first'));
    $second = $client->stamp(FileToStamp::fromContent('second'));

    $calendar->confirm($first, blockHeight: 700_111);

    expect($client->upgrade($first))->toBeTrue()
        ->and($first->isComplete())->toBeTrue()
        ->and($first->bitcoinBlockHeight())->toBe(700_111)
        ->and($client->upgrade($second))->toBeFalse()
        ->and($second->isPending())->toBeTrue();
});

it('fails when too few calendars accept the stamp', function (): void {
    // A calendar client where every calendar fails, with a threshold of 1.
    $client = new ElephStamp(
        calendarClient: new class implements CalendarClient {
            public function submit(array $calendarUrls, string $digest): array
            {
                return array_map(
                    static fn(string $url): CalendarResponse => CalendarResponse::failure($url, new CalendarException('down')),
                    $calendarUrls,
                );
            }

            public function getTimestamps(array $requests): array
            {
                return array_map(
                    static fn(array $request): CalendarResponse => CalendarResponse::notFound($request['url']),
                    $requests,
                );
            }
        },
        calendarUrls: ['https://a.example'],
        randomSource: new DeterministicRandomSource,
    );

    $client->stamp(FileToStamp::fromContent('x'));
})->throws(StampingException::class, 'down');

it('tolerates a failing calendar during upgrade and still merges a good one', function (): void {
    $client = new ElephStamp(
        calendarClient: new class implements CalendarClient {
            public function submit(array $calendarUrls, string $digest): array
            {
                return array_map(static function (string $url) use ($digest): CalendarResponse {
                    $timestamp = new Timestamp($digest);
                    $timestamp->addAttestation(new PendingAttestation($url));

                    return CalendarResponse::success($url, $timestamp);
                }, $calendarUrls);
            }

            public function getTimestamps(array $requests): array
            {
                return array_map(static function (array $request): CalendarResponse {
                    if ($request['url'] === 'https://down.example') {
                        return CalendarResponse::failure($request['url'], new CalendarException('boom'));
                    }

                    $timestamp = new Timestamp($request['commitment']);
                    $timestamp->addAttestation(new BitcoinAttestation(700_222));

                    return CalendarResponse::success($request['url'], $timestamp);
                }, $requests);
            }
        },
        calendarUrls: ['https://down.example', 'https://up.example'],
        randomSource: new DeterministicRandomSource,
        upgradeWhitelist: ['https://*.example'],
    );

    $receipt = $client->stamp(FileToStamp::fromContent('resilient'));

    expect($client->upgrade($receipt))->toBeTrue()
        ->and($receipt->isComplete())->toBeTrue()
        ->and($receipt->bitcoinBlockHeight())->toBe(700_222);
});

it('skips calendars outside the upgrade whitelist', function (): void {
    $calendar = new FakeCalendarClient;
    $client = new ElephStamp(
        calendarClient: $calendar,
        calendarUrls: [ElephStamp::FAKE_CALENDAR_URL],
        randomSource: new DeterministicRandomSource,
        upgradeWhitelist: ['https://only.trusted.example'],
    );

    $receipt = $client->stamp(FileToStamp::fromContent('x'));
    $calendar->confirmAll();

    // The fake calendar URL is not whitelisted, so upgrade contacts nothing.
    expect($client->upgrade($receipt))->toBeFalse()
        ->and($receipt->isPending())->toBeTrue();
});

it('describes a proof as a readable tree', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('describe me'));

    expect($receipt->describe())
        ->toContain('file sha256 digest: ' . $receipt->fileDigestHex())
        ->toContain('sha256')
        ->toContain('pending attestation → ' . ElephStamp::FAKE_CALENDAR_URL);

    $client->fakeCalendar()->confirmAll(650_000);
    $client->upgrade($receipt);

    expect($receipt->describe())->toContain('bitcoin attestation → block 650000');
});

it('skips a hostile calendar returning a timestamp for a different commitment', function (): void {
    $client = new ElephStamp(
        calendarClient: new class implements CalendarClient {
            public function submit(array $calendarUrls, string $digest): array
            {
                return array_map(static function (string $url) use ($digest): CalendarResponse {
                    $timestamp = new Timestamp($digest);
                    $timestamp->addAttestation(new PendingAttestation($url));

                    return CalendarResponse::success($url, $timestamp);
                }, $calendarUrls);
            }

            public function getTimestamps(array $requests): array
            {
                return array_map(static function (array $request): CalendarResponse {
                    // A forged proof for a commitment nobody asked about.
                    $timestamp = new Timestamp(hash('sha256', 'evil', binary: true));
                    $timestamp->addAttestation(new BitcoinAttestation(1));

                    return CalendarResponse::success($request['url'], $timestamp);
                }, $requests);
            }
        },
        calendarUrls: ['https://a.example'],
        randomSource: new DeterministicRandomSource,
        upgradeWhitelist: ['https://a.example'],
    );

    $receipt = $client->stamp(FileToStamp::fromContent('target'));

    expect($client->upgrade($receipt))->toBeFalse()
        ->and($receipt->isPending())->toBeTrue();
});

it('reports no change when a completed receipt is upgraded again', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('idempotent'));

    $client->fakeCalendar()->confirmAll();

    expect($client->upgrade($receipt))->toBeTrue();

    $bytes = $receipt->toBytes();

    expect($client->upgrade($receipt))->toBeFalse()
        ->and($receipt->toBytes())->toBe($bytes);
});

it('rejects a forged non-pending attestation in a submission response', function (): void {
    $client = new ElephStamp(
        calendarClient: new class implements CalendarClient {
            public function submit(array $calendarUrls, string $digest): array
            {
                return array_map(static function (string $url) use ($digest): CalendarResponse {
                    // A hostile calendar claiming an instant Bitcoin anchor.
                    $timestamp = new Timestamp($digest);
                    $timestamp->addAttestation(new BitcoinAttestation(1));

                    return CalendarResponse::success($url, $timestamp);
                }, $calendarUrls);
            }

            public function getTimestamps(array $requests): array
            {
                return [];
            }
        },
        calendarUrls: ['https://a.example'],
        randomSource: new DeterministicRandomSource,
    );

    $client->stamp(FileToStamp::fromContent('target'));
})->throws(StampingException::class, 'non-pending attestation');

it('requires two calendars by default, one when a single calendar is configured', function (): void {
    // 1 accepting calendar out of 4 defaults: below the default 2-of-n threshold.
    $oneOfMany = new ElephStamp(
        calendarClient: new class implements CalendarClient {
            public function submit(array $calendarUrls, string $digest): array
            {
                return array_map(static function (string $url) use ($digest): CalendarResponse {
                    if ($url !== 'https://a.example') {
                        return CalendarResponse::failure($url, new CalendarException('down'));
                    }

                    $timestamp = new Timestamp($digest);
                    $timestamp->addAttestation(new PendingAttestation($url));

                    return CalendarResponse::success($url, $timestamp);
                }, $calendarUrls);
            }

            public function getTimestamps(array $requests): array
            {
                return [];
            }
        },
        calendarUrls: ['https://a.example', 'https://b.example', 'https://c.example', 'https://d.example'],
        randomSource: new DeterministicRandomSource,
    );

    expect(static fn(): Receipt => $oneOfMany->stamp(FileToStamp::fromContent('x')))
        ->toThrow(StampingException::class, 'required 2');

    // A single-calendar setup still works without an explicit threshold.
    expect(ElephStamp::fake()->stamp(FileToStamp::fromContent('x'))->isPending())->toBeTrue();
});

it('rejects duplicate calendar URLs', function (): void {
    new ElephStamp(calendarUrls: ['https://a.example', 'https://a.example/'], requiredCalendars: 2);
})->throws(InvalidInputException::class, 'unique');

it('rejects plaintext http calendar URLs', function (): void {
    new ElephStamp(calendarUrls: ['http://a.example']);
})->throws(InvalidInputException::class, 'https');

it('rejects plaintext http whitelist patterns', function (): void {
    new ElephStamp(calendarUrls: ['https://a.example'], upgradeWhitelist: ['http://*.example']);
})->throws(InvalidInputException::class, 'https');

it('rejects an out-of-range required calendar threshold', function (): void {
    new ElephStamp(calendarUrls: ['https://a.example'], requiredCalendars: 2);
})->throws(InvalidInputException::class);

it('throws when asking for the fake calendar of a real client', function (): void {
    new ElephStamp(calendarUrls: ['https://a.example'])->fakeCalendar();
})->throws(InvalidInputException::class);

it('saves and reloads a receipt from disk', function (): void {
    $receipt = ElephStamp::fake()->stamp(FileToStamp::fromContent('persist me'));
    $path = makeTempPath();

    $receipt->saveToPath($path);

    expect(Receipt::fromPath($path)->toBytes())->toBe($receipt->toBytes());
});
