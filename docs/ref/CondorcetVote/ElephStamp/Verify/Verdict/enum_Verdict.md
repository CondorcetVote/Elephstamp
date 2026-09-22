> CondorcetVote \ [ElephStamp](../../readme.md) \ **Verdict**
# Enum Verdict
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/Verdict.php#L10)

## Description
The overall conclusion of verifying a receipt.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| AwaitingConfirmations | `public const AwaitingConfirmations = \CondorcetVote\ElephStamp\Verify\Verdict::AwaitingConfirmations` | _Same as {@see Verified}, except every matching block is still too recent to reach the required confirmations._ |
| Failed | `public const Failed = \CondorcetVote\ElephStamp\Verify\Verdict::Failed` | _The file does not match the proof, or a block's merkle root differs from the proof's and no other attestation is verified. The proof is not valid for this file._ |
| Inconclusive | `public const Inconclusive = \CondorcetVote\ElephStamp\Verify\Verdict::Inconclusive` | _There are Bitcoin attestations, but none could be checked: the source was unavailable, or the attestations are not computable._ |
| Pending | `public const Pending = \CondorcetVote\ElephStamp\Verify\Verdict::Pending` | _The proof carries no Bitcoin attestation yet; upgrade it first._ |
| Verified | `public const Verified = \CondorcetVote\ElephStamp\Verify\Verdict::Verified` | _The file matches the proof (when given) and at least one Bitcoin attestation is confirmed by a block with the expected merkle root._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [name(...)](property_name.md) | __ |


## Public Representation
```php
enum CondorcetVote\ElephStamp\Verify\Verdict implements UnitEnum
{
    case Verified;
    case AwaitingConfirmations;
    case Failed;
    case Pending;
    case Inconclusive;
    // Constants
    public const AwaitingConfirmations = \CondorcetVote\ElephStamp\Verify\Verdict::AwaitingConfirmations;
    public const Failed = \CondorcetVote\ElephStamp\Verify\Verdict::Failed;
    public const Inconclusive = \CondorcetVote\ElephStamp\Verify\Verdict::Inconclusive;
    public const Pending = \CondorcetVote\ElephStamp\Verify\Verdict::Pending;
    public const Verified = \CondorcetVote\ElephStamp\Verify\Verdict::Verified;

    // Properties
    public protected(set) readonly string $name;

}
```

## Full Representation
```php
enum CondorcetVote\ElephStamp\Verify\Verdict implements UnitEnum
{
    case Verified;
    case AwaitingConfirmations;
    case Failed;
    case Pending;
    case Inconclusive;
    // Constants
    public const AwaitingConfirmations = \CondorcetVote\ElephStamp\Verify\Verdict::AwaitingConfirmations;
    public const Failed = \CondorcetVote\ElephStamp\Verify\Verdict::Failed;
    public const Inconclusive = \CondorcetVote\ElephStamp\Verify\Verdict::Inconclusive;
    public const Pending = \CondorcetVote\ElephStamp\Verify\Verdict::Pending;
    public const Verified = \CondorcetVote\ElephStamp\Verify\Verdict::Verified;

    // Properties
    public protected(set) readonly string $name;

}
```