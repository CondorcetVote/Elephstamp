> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **CalendarSubmission**
# Class CalendarSubmission
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Inspection/CalendarSubmission.php#L12)

## Description
One pending attestation of a proof, seen from the calendar's point of view.
## Elements

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [calendarUrl(...)](property_calendarUrl.md) | __ |
| [commitment(...)](property_commitment.md) | __ |
| [confirmedBlockHeights(...)](property_confirmedBlockHeights.md) | __ |
| [recordedAt(...)](property_recordedAt.md) | __ |
| [upgradable(...)](property_upgradable.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [commitmentHex(...)](method_commitmentHex.md) | __ |
| [isConfirmed(...)](method_isConfirmed.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\CalendarSubmission
{

    // Properties
    public protected(set) readonly string $calendarUrl;
    public protected(set) readonly ?string $commitment;
    public protected(set) readonly array $confirmedBlockHeights;
    public protected(set) readonly ?DateTimeImmutable $recordedAt;
    public protected(set) readonly bool $upgradable;

    // Methods
    public function __construct( string $calendarUrl, ?string $commitment, ?DateTimeImmutable $recordedAt, bool $upgradable, array $confirmedBlockHeights );
    public function commitmentHex( ): ?string;
    public function isConfirmed( ): bool;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\CalendarSubmission
{

    // Properties
    public protected(set) readonly string $calendarUrl;
    public protected(set) readonly ?string $commitment;
    public protected(set) readonly array $confirmedBlockHeights;
    public protected(set) readonly ?DateTimeImmutable $recordedAt;
    public protected(set) readonly bool $upgradable;

    // Methods
    public function __construct( string $calendarUrl, ?string $commitment, ?DateTimeImmutable $recordedAt, bool $upgradable, array $confirmedBlockHeights );
    public function commitmentHex( ): ?string;
    public function isConfirmed( ): bool;

}
```