> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **ProofInspector**
# Class ProofInspector
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Inspection/ProofInspector.php#L17)

## Description
Walks a proof tree and gathers what each calendar submission is up to.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| EARLIEST_PLAUSIBLE_TIME | `private const int EARLIEST_PLAUSIBLE_TIME = 1451606400` | _Earliest plausible calendar time (OpenTimestamps did not exist before 2016)._ |
| PER_SECOND_KEY_LENGTH | `private const int PER_SECOND_KEY_LENGTH = 8` | __ |
| RECORDED_AT_LENGTH | `private const int RECORDED_AT_LENGTH = 4` | _The reference calendar server records the submission time as a 4-byte big-endian Unix timestamp prepended right before an 8-byte per-second key is appended; the pending attestation hangs off that last..._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [inspect(...)](method_inspect.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\ProofInspector
{

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Calendar\CalendarWhitelist $whitelist );
    public function inspect( CondorcetVote\ElephStamp\Receipt $receipt ): CondorcetVote\ElephStamp\Console\Inspection\ProofInspection;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\ProofInspector
{
    // Constants
    private const int EARLIEST_PLAUSIBLE_TIME = 1451606400;
    private const int PER_SECOND_KEY_LENGTH = 8;
    private const int RECORDED_AT_LENGTH = 4;

    // Properties
    private readonly CondorcetVote\ElephStamp\Calendar\CalendarWhitelist $whitelist;

    // Static Methods
    private static function blockHeightsBelow( CondorcetVote\ElephStamp\Timestamp $node ): array;
    private static function recordedAt( array $path ): ?DateTimeImmutable;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Calendar\CalendarWhitelist $whitelist );
    public function inspect( CondorcetVote\ElephStamp\Receipt $receipt ): CondorcetVote\ElephStamp\Console\Inspection\ProofInspection;

}
```