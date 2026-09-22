> CondorcetVote \ [ElephStamp](../../readme.md) \ **FakeBlockHeaderSource**
# Class FakeBlockHeaderSource
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/FakeBlockHeaderSource.php#L20)

## Description
In-memory block headers for tests and local environments.

Register the blocks a proof claims, then verify it without any network.
{@see \CondorcetVote\ElephStamp\Verify\anchor()} does the common thing: it registers, for every Bitcoin
attestation of a receipt, a block whose merkle root is the one the receipt
recomputes, so the receipt verifies.
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [addBlock(...)](method_addBlock.md) | _Register a block. Its hash is derived from the height, since nothing here is real._ |
| [anchor(...)](method_anchor.md) | _Register a block for every Bitcoin attestation of the receipt, so that verifying it succeeds._ |
| [blockHeader(...)](method_blockHeader.md) | __ |
| [describe(...)](method_describe.md) | __ |
| [reset(...)](method_reset.md) | _Forget every registered block._ |
| [setTipHeight(...)](method_setTipHeight.md) | _Pretend the chain has grown to this height. Defaults to the highest registered block plus enough for {@see Verifier::DEFAULT_REQUIRED_CONFIRMATIONS}._ |
| [tipHeight(...)](method_tipHeight.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource implements CondorcetVote\ElephStamp\Verify\BlockHeaderSource
{

    // Methods
    public function addBlock( int $height, string $merkleRoot, [ ?DateTimeImmutable $time = null ] ): void;
    public function anchor( CondorcetVote\ElephStamp\Receipt $receipt, [ ?DateTimeImmutable $time = null ] ): void;
    public function blockHeader( int $height ): CondorcetVote\ElephStamp\Verify\BlockHeader;
    public function describe( ): string;
    public function reset( ): void;
    public function setTipHeight( int $tipHeight ): void;
    public function tipHeight( ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource implements CondorcetVote\ElephStamp\Verify\BlockHeaderSource
{

    // Properties
    private array $headers = [];
    private ?int $tipHeight = null;

    // Methods
    public function addBlock( int $height, string $merkleRoot, [ ?DateTimeImmutable $time = null ] ): void;
    public function anchor( CondorcetVote\ElephStamp\Receipt $receipt, [ ?DateTimeImmutable $time = null ] ): void;
    public function blockHeader( int $height ): CondorcetVote\ElephStamp\Verify\BlockHeader;
    public function describe( ): string;
    public function reset( ): void;
    public function setTipHeight( int $tipHeight ): void;
    public function tipHeight( ): int;

}
```