> CondorcetVote \ [ElephStamp](../../readme.md) \ **HttpCalendarClient**
# Class HttpCalendarClient
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/HttpCalendarClient.php#L23)

## Description
Calendar client backed by the Symfony HTTP client.

Requests within a batch are dispatched concurrently: all of them are started
before any response is read, so a batch takes as long as the slowest single
calendar rather than the sum of them. A custom {@see \Symfony\Contracts\HttpClient\HttpClientInterface}
can be injected (for example {@see \Symfony\Component\HttpClient\MockHttpClient}
in tests, or a client with tuned timeouts/proxy in production).
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| ACCEPT | `private const string ACCEPT = 'application/vnd.opentimestamps.v1'` | _Content type negotiated with calendar servers._ |
| MAX_RESPONSE_BYTES | `private const int MAX_RESPONSE_BYTES = 10000` | _Hard cap on a calendar response body, matching the reference client._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [getTimestamps(...)](method_getTimestamps.md) | __ |
| [submit(...)](method_submit.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Calendar\HttpCalendarClient implements CondorcetVote\ElephStamp\Calendar\CalendarClient
{

    // Methods
    public function __construct( [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, string $userAgent = 'ElephStamp' ] );
    public function getTimestamps( array $requests ): array;
    public function submit( array $calendarUrls, string $digest ): array;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Calendar\HttpCalendarClient implements CondorcetVote\ElephStamp\Calendar\CalendarClient
{
    // Constants
    private const string ACCEPT = 'application/vnd.opentimestamps.v1';
    private const int MAX_RESPONSE_BYTES = 10000;

    // Properties
    private readonly Symfony\Contracts\HttpClient\HttpClientInterface $httpClient;
    private readonly string $userAgent;

    // Methods
    public function __construct( [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, string $userAgent = 'ElephStamp' ] );
    public function getTimestamps( array $requests ): array;
    public function submit( array $calendarUrls, string $digest ): array;

}
```