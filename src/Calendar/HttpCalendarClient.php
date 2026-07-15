<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Calendar;

use CondorcetVote\ElephStamp\Serialization\Deserializer;
use CondorcetVote\ElephStamp\Timestamp;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\{HttpClientInterface, ResponseInterface};
use CondorcetVote\ElephStamp\Exception\{CalendarException, SerializationException};

/**
 * Calendar client backed by the Symfony HTTP client.
 *
 * Requests within a batch are dispatched concurrently: all of them are started
 * before any response is read, so a batch takes as long as the slowest single
 * calendar rather than the sum of them. A custom {@see HttpClientInterface}
 * can be injected (for example {@see \Symfony\Component\HttpClient\MockHttpClient}
 * in tests, or a client with tuned timeouts/proxy in production).
 */
final class HttpCalendarClient implements CalendarClient
{
    /**
     * Content type negotiated with calendar servers.
     */
    private const string ACCEPT = 'application/vnd.opentimestamps.v1';

    /**
     * Hard cap on a calendar response body, matching the reference client.
     */
    private const int MAX_RESPONSE_BYTES = 10_000;

    private readonly HttpClientInterface $httpClient;

    public function __construct(
        ?HttpClientInterface $httpClient = null,
        private readonly string $userAgent = 'ElephStamp',
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function submit(array $calendarUrls, string $digest): array
    {
        $requests = array_map(
            static fn(string $url): array => [
                'url' => $url,
                'method' => 'POST',
                'path' => 'digest',
                'expectedMsg' => $digest,
                'options' => ['body' => $digest],
            ],
            $calendarUrls,
        );

        return $this->dispatch($requests, allowNotFound: false);
    }

    public function getTimestamps(array $requests): array
    {
        $httpRequests = array_map(
            static fn(array $request): array => [
                'url' => $request['url'],
                'method' => 'GET',
                'path' => 'timestamp/' . bin2hex($request['commitment']),
                'expectedMsg' => $request['commitment'],
                'options' => [],
            ],
            $requests,
        );

        return $this->dispatch($httpRequests, allowNotFound: true);
    }

    /**
     * Start every request, then resolve them; the transfers run concurrently.
     *
     * @param list<array{url: string, method: string, path: string, expectedMsg: string, options: array<string, mixed>}> $requests
     *
     * @return list<CalendarResponse>
     */
    private function dispatch(array $requests, bool $allowNotFound): array
    {
        $responses = [];

        foreach ($requests as $index => $request) {
            $responses[$index] = $this->httpClient->request(
                $request['method'],
                $this->endpoint($request['url'], $request['path']),
                $this->requestOptions($request['options']),
            );
        }

        $results = [];

        foreach ($requests as $index => $request) {
            $results[] = $this->resolve($request['url'], $responses[$index], $request['expectedMsg'], $allowNotFound);
        }

        return $results;
    }

    private function resolve(string $calendarUrl, ResponseInterface $response, string $expectedMsg, bool $allowNotFound): CalendarResponse
    {
        try {
            $status = $response->getStatusCode();

            if ($allowNotFound && $status === 404) {
                return CalendarResponse::notFound($calendarUrl);
            }

            if ($status !== 200) {
                return CalendarResponse::failure($calendarUrl, new CalendarException(\sprintf('Calendar %s returned unexpected status %d', $calendarUrl, $status)));
            }

            $body = $response->getContent(throw: false);
        } catch (HttpClientException $exception) {
            return CalendarResponse::failure($calendarUrl, new CalendarException(\sprintf('Calendar %s: %s', $calendarUrl, $exception->getMessage()), previous: $exception));
        }

        if (\strlen($body) > self::MAX_RESPONSE_BYTES) {
            return CalendarResponse::failure($calendarUrl, new CalendarException(\sprintf('Calendar %s response exceeded the size limit', $calendarUrl)));
        }

        try {
            return CalendarResponse::success($calendarUrl, Timestamp::deserialize(new Deserializer($body), $expectedMsg));
        } catch (SerializationException $exception) {
            return CalendarResponse::failure($calendarUrl, $exception);
        }
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function requestOptions(array $extra = []): array
    {
        return [
            'headers' => [
                'Accept' => self::ACCEPT,
                'User-Agent' => $this->userAgent,
            ],
        ] + $extra;
    }

    private function endpoint(string $calendarUrl, string $path): string
    {
        return rtrim($calendarUrl, '/') . '/' . $path;
    }
}
