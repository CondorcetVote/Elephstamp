> CondorcetVote \ [ElephStamp](../../readme.md) \ **CalendarUpgradeResult**
# Class CalendarUpgradeResult
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Upgrade/CalendarUpgradeResult.php#L12)

## Description
The outcome of polling one calendar for one pending commitment.
## Elements

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [blockHeight(...)](property_blockHeight.md) | __ |
| [calendarUrl(...)](property_calendarUrl.md) | __ |
| [commitment(...)](property_commitment.md) | __ |
| [error(...)](property_error.md) | __ |
| [outcome(...)](property_outcome.md) | __ |
| [verifications(...)](property_verifications.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [claimedBlockHeight(...)](method_claimedBlockHeight.md) | _The block the calendar's answer names, verified or not: the height of its shallowest Bitcoin attestation. Null when the answer named none._ |
| [commitmentHex(...)](method_commitmentHex.md) | _The commitment as a lower-case hex string._ |
| [confirmations(...)](method_confirmations.md) | _The confirmations of the shallowest block the calendar's answer names, itself included. Null when nothing was checked or the block's depth is unknown (its header could not be fetched)._ |
| [verified(...)](method_verified.md) | _Whether every Bitcoin attestation in the calendar's answer was checked against its block and found matching and deep enough; null when there was nothing to check._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Upgrade\CalendarUpgradeResult
{

    // Properties
    public protected(set) readonly ?int $blockHeight;
    public protected(set) readonly string $calendarUrl;
    public protected(set) readonly string $commitment;
    public protected(set) readonly ?string $error;
    public protected(set) readonly CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome;
    public protected(set) readonly array $verifications;

    // Methods
    public function __construct( string $calendarUrl, string $commitment, CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome, [ ?string $error = null, ?int $blockHeight = null, array $verifications = [] ] );
    public function claimedBlockHeight( ): ?int;
    public function commitmentHex( ): string;
    public function confirmations( ): ?int;
    public function verified( ): ?bool;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Upgrade\CalendarUpgradeResult
{

    // Properties
    public protected(set) readonly ?int $blockHeight;
    public protected(set) readonly string $calendarUrl;
    public protected(set) readonly string $commitment;
    public protected(set) readonly ?string $error;
    public protected(set) readonly CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome;
    public protected(set) readonly array $verifications;

    // Methods
    public function __construct( string $calendarUrl, string $commitment, CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome, [ ?string $error = null, ?int $blockHeight = null, array $verifications = [] ] );
    public function claimedBlockHeight( ): ?int;
    public function commitmentHex( ): string;
    public function confirmations( ): ?int;
    public function verified( ): ?bool;

}
```