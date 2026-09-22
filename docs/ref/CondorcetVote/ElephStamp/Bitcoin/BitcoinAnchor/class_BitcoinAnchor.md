> CondorcetVote \ [ElephStamp](../../readme.md) \ **BitcoinAnchor**
# Class BitcoinAnchor
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Bitcoin/BitcoinAnchor.php#L17)

## Description
One Bitcoin attestation of a proof together with what the proof path
reveals about it: the block's merkle root it commits to and, when the path
follows the usual layout, the transaction that carries the commitment.

Nothing here is verified against the blockchain; these are the values a
verifier would compare with the block header and the transaction.
## Elements

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [attestation(...)](property_attestation.md) | __ |
| [merkleRoot(...)](property_merkleRoot.md) | __ |
| [transaction(...)](property_transaction.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [blockHeight(...)](method_blockHeight.md) | __ |
| [merkleRootHex(...)](method_merkleRootHex.md) | _The merkle root as block explorers display it (byte-reversed hex)._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor
{

    // Properties
    public protected(set) readonly CondorcetVote\ElephStamp\Attestation\BitcoinAttestation $attestation;
    public protected(set) readonly ?string $merkleRoot;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction $transaction;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Attestation\BitcoinAttestation $attestation, ?string $merkleRoot, ?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction $transaction );
    public function blockHeight( ): int;
    public function merkleRootHex( ): ?string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor
{

    // Properties
    public protected(set) readonly CondorcetVote\ElephStamp\Attestation\BitcoinAttestation $attestation;
    public protected(set) readonly ?string $merkleRoot;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction $transaction;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Attestation\BitcoinAttestation $attestation, ?string $merkleRoot, ?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction $transaction );
    public function blockHeight( ): int;
    public function merkleRootHex( ): ?string;

}
```