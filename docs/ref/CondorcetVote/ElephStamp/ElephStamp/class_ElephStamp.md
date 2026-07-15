> CondorcetVote \ **ElephStamp**
# Class ElephStamp
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L19)

## Description
The library entry point: submit timestamp requests to calendar servers and
refresh receipts as they get confirmed.

Verification against the Bitcoin blockchain is deliberately out of scope.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| DEFAULT_CALENDAR_URLS | `public const array DEFAULT_CALENDAR_URLS = ['https://a.pool.opentimestamps.org', 'https://b.pool.opentimestamps.org', 'https://a.pool.eternitywall.com', 'https://ots.btc.catallaxy.com']` | _The public aggregator calendars used by default._ |
| DEFAULT_UPGRADE_WHITELIST | `public const array DEFAULT_UPGRADE_WHITELIST = ['https://*.calendar.opentimestamps.org', 'https://*.calendar.eternitywall.com', 'https://*.calendar.catallaxy.com']` | _Host patterns an upgrade is allowed to contact by default._ |
| FAKE_CALENDAR_URL | `public const string FAKE_CALENDAR_URL = 'https://fake.calendar.elephstamp'` | _Calendar URL used by the fake client._ |
| NONCE_LENGTH | `private const int NONCE_LENGTH = 16` | _Length of the per-file privacy nonce, in bytes._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [fake(...)](method_fake.md) | _Build a fully offline, deterministic client for tests and local environments._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [fakeCalendar(...)](method_fakeCalendar.md) | _The fake calendar backing this client, for driving its lifecycle in tests._ |
| [stamp(...)](method_stamp.md) | _Timestamp a single file._ |
| [stampMany(...)](method_stampMany.md) | _Timestamp several files at once, sharing a single calendar submission._ |
| [upgrade(...)](method_upgrade.md) | _Query the calendars for confirmations and merge them into the receipt._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\ElephStamp
{
    // Constants
    public const array DEFAULT_CALENDAR_URLS = ['https://a.pool.opentimestamps.org', 'https://b.pool.opentimestamps.org', 'https://a.pool.eternitywall.com', 'https://ots.btc.catallaxy.com'];
    public const array DEFAULT_UPGRADE_WHITELIST = ['https://*.calendar.opentimestamps.org', 'https://*.calendar.eternitywall.com', 'https://*.calendar.catallaxy.com'];
    public const string FAKE_CALENDAR_URL = 'https://fake.calendar.elephstamp';

    // Static Methods
    public static function fake( [ ?CondorcetVote\ElephStamp\Calendar\FakeCalendarClient $calendar = null ] ): self;

    // Methods
    public function __construct( [ ?CondorcetVote\ElephStamp\Calendar\CalendarClient $calendarClient = null, ?array $calendarUrls = null, int $requiredCalendars = 1, ?CondorcetVote\ElephStamp\Operation\HashOperation $hashOperation = null, ?CondorcetVote\ElephStamp\Random\RandomSource $randomSource = null, ?array $upgradeWhitelist = null ] );
    public function fakeCalendar( ): CondorcetVote\ElephStamp\Calendar\FakeCalendarClient;
    public function stamp( CondorcetVote\ElephStamp\FileToStamp $file ): CondorcetVote\ElephStamp\Receipt;
    public function stampMany( [ CondorcetVote\ElephStamp\FileToStamp ...$files ] ): array;
    public function upgrade( CondorcetVote\ElephStamp\Receipt $receipt ): bool;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\ElephStamp
{
    // Constants
    public const array DEFAULT_CALENDAR_URLS = ['https://a.pool.opentimestamps.org', 'https://b.pool.opentimestamps.org', 'https://a.pool.eternitywall.com', 'https://ots.btc.catallaxy.com'];
    public const array DEFAULT_UPGRADE_WHITELIST = ['https://*.calendar.opentimestamps.org', 'https://*.calendar.eternitywall.com', 'https://*.calendar.catallaxy.com'];
    public const string FAKE_CALENDAR_URL = 'https://fake.calendar.elephstamp';
    private const int NONCE_LENGTH = 16;

    // Properties
    private readonly CondorcetVote\ElephStamp\Calendar\CalendarClient $calendarClient;
    private readonly array $calendarUrls;
    private readonly CondorcetVote\ElephStamp\Operation\HashOperation $hashOperation;
    private readonly CondorcetVote\ElephStamp\Random\RandomSource $randomSource;
    private readonly int $requiredCalendars;
    private readonly CondorcetVote\ElephStamp\Calendar\CalendarWhitelist $upgradeWhitelist;

    // Static Methods
    public static function fake( [ ?CondorcetVote\ElephStamp\Calendar\FakeCalendarClient $calendar = null ] ): self;

    // Methods
    public function __construct( [ ?CondorcetVote\ElephStamp\Calendar\CalendarClient $calendarClient = null, ?array $calendarUrls = null, int $requiredCalendars = 1, ?CondorcetVote\ElephStamp\Operation\HashOperation $hashOperation = null, ?CondorcetVote\ElephStamp\Random\RandomSource $randomSource = null, ?array $upgradeWhitelist = null ] );
    public function fakeCalendar( ): CondorcetVote\ElephStamp\Calendar\FakeCalendarClient;
    public function stamp( CondorcetVote\ElephStamp\FileToStamp $file ): CondorcetVote\ElephStamp\Receipt;
    public function stampMany( [ CondorcetVote\ElephStamp\FileToStamp ...$files ] ): array;
    public function upgrade( CondorcetVote\ElephStamp\Receipt $receipt ): bool;

}
```