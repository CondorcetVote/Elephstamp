> CondorcetVote \ [ElephStamp](../../readme.md) \ **FakeCalendarClient**
# Class FakeCalendarClient
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/FakeCalendarClient.php#L21)

## Description
In-memory calendar for tests and local integration environments.

Behaves like a real calendar without any network: every submitted digest
starts pending, and the test drives it to completion by calling
{@see \CondorcetVote\ElephStamp\Calendar\confirm()} or {@see \CondorcetVote\ElephStamp\Calendar\confirmAll()}, simulating Bitcoin confirmation.

Responses are deterministic, so a stamp produced with a
{@see \CondorcetVote\ElephStamp\Random\DeterministicRandomSource} is fully
reproducible.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| DEFAULT_BLOCK_HEIGHT | `public const int DEFAULT_BLOCK_HEIGHT = 800000` | _Default block height reported once a commitment is confirmed._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [confirm(...)](method_confirm.md) | _Confirm the commitments a specific receipt is pending on, as if Bitcoin had included them._ |
| [confirmAll(...)](method_confirmAll.md) | _Confirm every digest submitted so far._ |
| [getTimestamps(...)](method_getTimestamps.md) | __ |
| [reset(...)](method_reset.md) | _Forget all submitted and confirmed state._ |
| [submit(...)](method_submit.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Calendar\FakeCalendarClient implements CondorcetVote\ElephStamp\Calendar\CalendarClient
{
    // Constants
    public const int DEFAULT_BLOCK_HEIGHT = 800000;

    // Methods
    public function __construct( [ int $defaultBlockHeight = 800000 ] );
    public function confirm( CondorcetVote\ElephStamp\Receipt $receipt, [ ?int $blockHeight = null ] ): void;
    public function confirmAll( [ ?int $blockHeight = null ] ): void;
    public function getTimestamps( array $requests ): array;
    public function reset( ): void;
    public function submit( array $calendarUrls, string $digest ): array;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Calendar\FakeCalendarClient implements CondorcetVote\ElephStamp\Calendar\CalendarClient
{
    // Constants
    public const int DEFAULT_BLOCK_HEIGHT = 800000;

    // Properties
    private array $confirmed = [];
    private readonly int $defaultBlockHeight;
    private array $submitted = [];

    // Methods
    public function __construct( [ int $defaultBlockHeight = 800000 ] );
    public function confirm( CondorcetVote\ElephStamp\Receipt $receipt, [ ?int $blockHeight = null ] ): void;
    public function confirmAll( [ ?int $blockHeight = null ] ): void;
    public function getTimestamps( array $requests ): array;
    public function reset( ): void;
    public function submit( array $calendarUrls, string $digest ): array;

}
```