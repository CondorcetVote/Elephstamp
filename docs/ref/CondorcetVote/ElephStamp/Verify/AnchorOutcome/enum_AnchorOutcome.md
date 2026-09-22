> CondorcetVote \ [ElephStamp](../../readme.md) \ **AnchorOutcome**
# Enum AnchorOutcome
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/AnchorOutcome.php#L10)

## Description
What checking one Bitcoin attestation against a block header gave.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| AwaitingConfirmations | `public const AwaitingConfirmations = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::AwaitingConfirmations` | _The merkle root matches, but the block is too recent: fewer confirmations than required. Verify again later._ |
| BlockUnavailable | `public const BlockUnavailable = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::BlockUnavailable` | _The source could not provide the header (unreachable, unknown block, inconsistent answer). Nothing can be concluded._ |
| MerkleRootMismatch | `public const MerkleRootMismatch = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::MerkleRootMismatch` | _The block's merkle root differs from the proof's: the proof is corrupt, forged, or names the wrong block._ |
| NotComputable | `public const NotComputable = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::NotComputable` | _The proof's merkle root is unknown because the attestation sits below an operation this library cannot compute._ |
| Verified | `public const Verified = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::Verified` | _The block's merkle root equals the one the proof recomputes, and the block is buried under enough confirmations._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [name(...)](property_name.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [matches(...)](method_matches.md) | _Whether the merkle root was found to match, regardless of depth._ |


## Public Representation
```php
enum CondorcetVote\ElephStamp\Verify\AnchorOutcome implements UnitEnum
{
    case Verified;
    case AwaitingConfirmations;
    case MerkleRootMismatch;
    case BlockUnavailable;
    case NotComputable;
    // Constants
    public const AwaitingConfirmations = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::AwaitingConfirmations;
    public const BlockUnavailable = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::BlockUnavailable;
    public const MerkleRootMismatch = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::MerkleRootMismatch;
    public const NotComputable = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::NotComputable;
    public const Verified = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::Verified;

    // Properties
    public protected(set) readonly string $name;

    // Methods
    public function matches( ): bool;

}
```

## Full Representation
```php
enum CondorcetVote\ElephStamp\Verify\AnchorOutcome implements UnitEnum
{
    case Verified;
    case AwaitingConfirmations;
    case MerkleRootMismatch;
    case BlockUnavailable;
    case NotComputable;
    // Constants
    public const AwaitingConfirmations = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::AwaitingConfirmations;
    public const BlockUnavailable = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::BlockUnavailable;
    public const MerkleRootMismatch = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::MerkleRootMismatch;
    public const NotComputable = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::NotComputable;
    public const Verified = \CondorcetVote\ElephStamp\Verify\AnchorOutcome::Verified;

    // Properties
    public protected(set) readonly string $name;

    // Methods
    public function matches( ): bool;

}
```