> CondorcetVote \ [ElephStamp](../../readme.md) \ **AnchorVerification**
# Class AnchorVerification
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/AnchorVerification.php#L12)

## Description
One Bitcoin attestation checked against the block it names.
## Elements

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [anchor(...)](property_anchor.md) | __ |
| [confirmations(...)](property_confirmations.md) | __ |
| [error(...)](property_error.md) | __ |
| [header(...)](property_header.md) | __ |
| [outcome(...)](property_outcome.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [blockHeight(...)](method_blockHeight.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Verify\AnchorVerification
{

    // Properties
    public protected(set) readonly CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor $anchor;
    public protected(set) readonly ?int $confirmations;
    public protected(set) readonly ?string $error;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Verify\BlockHeader $header;
    public protected(set) readonly CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor $anchor, CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome, [ ?CondorcetVote\ElephStamp\Verify\BlockHeader $header = null, ?int $confirmations = null, ?string $error = null ] );
    public function blockHeight( ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Verify\AnchorVerification
{

    // Properties
    public protected(set) readonly CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor $anchor;
    public protected(set) readonly ?int $confirmations;
    public protected(set) readonly ?string $error;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Verify\BlockHeader $header;
    public protected(set) readonly CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor $anchor, CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome, [ ?CondorcetVote\ElephStamp\Verify\BlockHeader $header = null, ?int $confirmations = null, ?string $error = null ] );
    public function blockHeight( ): int;

}
```