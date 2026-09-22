> CondorcetVote \ [ElephStamp](../../readme.md) \ **AnchorLocator**
# Class AnchorLocator
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Bitcoin/AnchorLocator.php#L20)

## Description
Finds every Bitcoin attestation of a proof and recovers its transaction.

The path to a Bitcoin attestation ends with the transaction bytes, hashed
twice with SHA-256 (giving the transaction id), followed by the merkle
branch: for each level, the sibling hash is prepended or appended and the
result hashed twice again. Walking back from the attestation, the first
double-SHA-256 input that parses as a transaction is the transaction.
## Elements

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [locate(...)](method_locate.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Bitcoin\AnchorLocator
{

    // Static Methods
    public static function locate( CondorcetVote\ElephStamp\Timestamp $root ): array;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Bitcoin\AnchorLocator
{

    // Static Methods
    public static function locate( CondorcetVote\ElephStamp\Timestamp $root ): array;
    private static function recoverTransaction( array $ops, array $parents ): ?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction;
    private static function walk( CondorcetVote\ElephStamp\Timestamp $node, array $ops, array $parents, array &$anchors ): void;

}
```