> CondorcetVote \ [ElephStamp](../../readme.md) \ **MerkleTree**
# Class MerkleTree
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Merkle/MerkleTree.php#L18)

## Description
Builds the merkle tree that binds several file timestamps to a single
commitment, so one calendar submission covers them all.

The algorithm is structurally a merkle mountain range and is
consensus-critical: it is guaranteed never to change.
## Elements

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [build(...)](method_build.md) | _Merkelize a list of leaf timestamps in place, returning the tip._ |
| [concatenateThenSha256(...)](method_concatenateThenSha256.md) | _Combine two timestamps: append/prepend to reach the same concatenated message, then hash it. Returns the timestamp of the parent node._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Merkle\MerkleTree
{

    // Static Methods
    public static function build( array $leaves ): CondorcetVote\ElephStamp\Timestamp;
    public static function concatenateThenSha256( CondorcetVote\ElephStamp\Timestamp $left, CondorcetVote\ElephStamp\Timestamp $right ): CondorcetVote\ElephStamp\Timestamp;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Merkle\MerkleTree
{

    // Static Methods
    public static function build( array $leaves ): CondorcetVote\ElephStamp\Timestamp;
    public static function concatenateThenSha256( CondorcetVote\ElephStamp\Timestamp $left, CondorcetVote\ElephStamp\Timestamp $right ): CondorcetVote\ElephStamp\Timestamp;

}
```