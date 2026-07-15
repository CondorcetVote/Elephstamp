<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Calendar\HttpCalendarClient;
use CondorcetVote\ElephStamp\Serialization\Serializer;
use CondorcetVote\ElephStamp\Timestamp;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

function serializedTimestamp(string $msg, callable $configure): string
{
    $timestamp = new Timestamp($msg);
    $configure($timestamp);

    $serializer = new Serializer;
    $timestamp->serialize($serializer);

    return $serializer->getBytes();
}

it('submits a digest to several calendars and parses each pending response', function (): void {
    $digest = hash('sha256', 'payload', binary: true);
    $body = serializedTimestamp($digest, static function (Timestamp $t): void {
        $t->addAttestation(new PendingAttestation('https://a.example'));
    });

    $captured = [];
    $client = new HttpCalendarClient(new MockHttpClient(function (string $method, string $url, array $options) use (&$captured, $body): MockResponse {
        $captured[] = ['method' => $method, 'url' => $url, 'body' => $options['body'] ?? null];

        return new MockResponse($body);
    }));

    $responses = $client->submit(['https://a.example', 'https://b.example'], $digest);

    expect($responses)->toHaveCount(2)
        ->and($responses[0]->isSuccess())->toBeTrue()
        ->and($responses[0]->timestamp->msg)->toBe($digest)
        ->and($responses[1]->calendarUrl)->toBe('https://b.example')
        ->and($captured[0]['method'])->toBe('POST')
        ->and($captured[0]['url'])->toBe('https://a.example/digest')
        ->and($captured[0]['body'])->toBe($digest);
});

it('reports not-found for a commitment the calendar does not have (404)', function (): void {
    $client = new HttpCalendarClient(new MockHttpClient(new MockResponse('', ['http_code' => 404])));

    $responses = $client->getTimestamps([['url' => 'https://a.example', 'commitment' => 'commitment']]);

    expect($responses[0]->isNotFound())->toBeTrue()
        ->and($responses[0]->timestamp)->toBeNull();
});

it('parses an upgraded timestamp on 200', function (): void {
    $commitment = hash('sha256', 'tip', binary: true);
    $body = serializedTimestamp($commitment, static function (Timestamp $t): void {
        $t->addAttestation(new BitcoinAttestation(700_000));
    });

    $captured = [];
    $client = new HttpCalendarClient(new MockHttpClient(function (string $method, string $url) use (&$captured, $body): MockResponse {
        $captured = ['method' => $method, 'url' => $url];

        return new MockResponse($body);
    }));

    $responses = $client->getTimestamps([['url' => 'https://a.example', 'commitment' => $commitment]]);

    expect($responses[0]->isSuccess())->toBeTrue()
        ->and($responses[0]->timestamp->hasBitcoinAttestation())->toBeTrue()
        ->and($captured['method'])->toBe('GET')
        ->and($captured['url'])->toBe('https://a.example/timestamp/' . bin2hex($commitment));
});

it('captures an unexpected status as a failure instead of throwing', function (): void {
    $client = new HttpCalendarClient(new MockHttpClient(new MockResponse('', ['http_code' => 500])));

    $responses = $client->submit(['https://a.example'], 'digest');

    expect($responses[0]->isFailure())->toBeTrue()
        ->and($responses[0]->error?->getMessage())->toContain('unexpected status 500');
});

it('captures a malformed response body as a failure instead of throwing', function (): void {
    $client = new HttpCalendarClient(new MockHttpClient(new MockResponse('not a valid timestamp')));

    $responses = $client->submit(['https://a.example'], hash('sha256', 'x', binary: true));

    expect($responses[0]->isFailure())->toBeTrue();
});

it('contacts every calendar in a batch', function (): void {
    $digest = hash('sha256', 'payload', binary: true);
    $body = serializedTimestamp($digest, static function (Timestamp $t): void {
        $t->addAttestation(new PendingAttestation('https://a.example'));
    });

    $calls = 0;
    $client = new HttpCalendarClient(new MockHttpClient(function () use (&$calls, $body): MockResponse {
        ++$calls;

        return new MockResponse($body);
    }));

    $responses = $client->submit(['https://a.example', 'https://b.example', 'https://c.example'], $digest);

    expect($responses)->toHaveCount(3)
        ->and($calls)->toBe(3)
        ->and(array_filter($responses, static fn($r): bool => $r->isSuccess()))->toHaveCount(3);
});
