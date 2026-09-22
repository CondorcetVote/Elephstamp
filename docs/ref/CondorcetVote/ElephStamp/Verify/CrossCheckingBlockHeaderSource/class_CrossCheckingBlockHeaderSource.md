> CondorcetVote \ [ElephStamp](../../readme.md) \ **CrossCheckingBlockHeaderSource**
# Class CrossCheckingBlockHeaderSource
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/CrossCheckingBlockHeaderSource.php#L17)

## Description
Asks several sources and only answers when they all agree.

This bounds the trust placed in any single explorer: a header is accepted
only if every source returns the same hash, merkle root and time for the
height. The chain tip is the lowest one reported, so confirmations are
never overstated.
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [blockHeader(...)](method_blockHeader.md) | __ |
| [describe(...)](method_describe.md) | __ |
| [tipHeight(...)](method_tipHeight.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Verify\CrossCheckingBlockHeaderSource implements CondorcetVote\ElephStamp\Verify\BlockHeaderSource
{

    // Methods
    public function __construct( [ CondorcetVote\ElephStamp\Verify\BlockHeaderSource ...$sources ] );
    public function blockHeader( int $height ): CondorcetVote\ElephStamp\Verify\BlockHeader;
    public function describe( ): string;
    public function tipHeight( ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Verify\CrossCheckingBlockHeaderSource implements CondorcetVote\ElephStamp\Verify\BlockHeaderSource
{

    // Properties
    private readonly array $sources;

    // Methods
    public function __construct( [ CondorcetVote\ElephStamp\Verify\BlockHeaderSource ...$sources ] );
    public function blockHeader( int $height ): CondorcetVote\ElephStamp\Verify\BlockHeader;
    public function describe( ): string;
    public function tipHeight( ): int;

}
```