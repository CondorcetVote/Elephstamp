> CondorcetVote \ [ElephStamp](../../readme.md) \ **UpgradeReport**
# Class UpgradeReport
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Upgrade/UpgradeReport.php#L15)

## Description
Detailed account of one {@see \CondorcetVote\ElephStamp\ElephStamp::upgradeWithReport()} pass.

Holds one {@see \CondorcetVote\ElephStamp\Upgrade\CalendarUpgradeResult} per pending attestation found in the
receipt, including the ones that were not contacted because their calendar
is not whitelisted. A receipt that was already complete yields an empty
report.
## Elements

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [blockHeaderSource(...)](property_blockHeaderSource.md) | __ |
| [results(...)](property_results.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [changed(...)](method_changed.md) | _Whether the pass added anything new to the receipt._ |
| [count(...)](method_count.md) | _Number of results with the given outcome._ |
| [filter(...)](method_filter.md) | _Results with the given outcome, in polling order._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Upgrade\UpgradeReport
{

    // Properties
    public protected(set) readonly ?string $blockHeaderSource;
    public protected(set) readonly array $results;

    // Methods
    public function __construct( array $results, [ ?string $blockHeaderSource = null ] );
    public function changed( ): bool;
    public function count( CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome ): int;
    public function filter( CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome ): array;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Upgrade\UpgradeReport
{

    // Properties
    public protected(set) readonly ?string $blockHeaderSource;
    public protected(set) readonly array $results;

    // Methods
    public function __construct( array $results, [ ?string $blockHeaderSource = null ] );
    public function changed( ): bool;
    public function count( CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome ): int;
    public function filter( CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome ): array;

}
```