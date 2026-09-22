> CondorcetVote \ [ElephStamp](../../readme.md) \ **UpgradeOutcome**
# Enum UpgradeOutcome
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Upgrade/UpgradeOutcome.php#L10)

## Description
What happened when a single calendar was polled during an upgrade pass.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| Confirmed | `public const Confirmed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Confirmed` | _A Bitcoin attestation already hangs below this submission, so there was nothing left to ask the calendar. Only reported when polling an already complete receipt._ |
| Failed | `public const Failed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Failed` | _The calendar could not be reached or answered with a protocol error._ |
| Pending | `public const Pending = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Pending` | _The calendar has nothing yet for this commitment; poll again later._ |
| Rejected | `public const Rejected = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Rejected` | _The calendar answered with a timestamp that does not commit to the requested digest, or whose Bitcoin attestation names a block that does not commit to it: hostile or corrupt, so it was discarded._ |
| Skipped | `public const Skipped = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Skipped` | _The calendar URI is not on the upgrade whitelist, so it was not contacted._ |
| Unchanged | `public const Unchanged = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unchanged` | _The calendar returned a timestamp the proof already contained._ |
| Unconfirmed | `public const Unconfirmed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unconfirmed` | _The calendar's Bitcoin attestation matches its block, but that block is still too shallow (fewer confirmations than required), so the answer was not merged yet. Poll again later._ |
| Unverifiable | `public const Unverifiable = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unverifiable` | _The calendar's Bitcoin attestation could not be checked against the blockchain (block header source unavailable, unknown block, or an attestation below an operation this library cannot compute), so th..._ |
| Upgraded | `public const Upgraded = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Upgraded` | _The calendar returned an attestation that was merged into the proof._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [name(...)](property_name.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [wasContacted(...)](method_wasContacted.md) | _Whether this outcome means the calendar was actually contacted._ |


## Public Representation
```php
enum CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome implements UnitEnum
{
    case Upgraded;
    case Unchanged;
    case Pending;
    case Failed;
    case Rejected;
    case Unconfirmed;
    case Unverifiable;
    case Skipped;
    case Confirmed;
    // Constants
    public const Confirmed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Confirmed;
    public const Failed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Failed;
    public const Pending = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Pending;
    public const Rejected = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Rejected;
    public const Skipped = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Skipped;
    public const Unchanged = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unchanged;
    public const Unconfirmed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unconfirmed;
    public const Unverifiable = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unverifiable;
    public const Upgraded = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Upgraded;

    // Properties
    public protected(set) readonly string $name;

    // Methods
    public function wasContacted( ): bool;

}
```

## Full Representation
```php
enum CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome implements UnitEnum
{
    case Upgraded;
    case Unchanged;
    case Pending;
    case Failed;
    case Rejected;
    case Unconfirmed;
    case Unverifiable;
    case Skipped;
    case Confirmed;
    // Constants
    public const Confirmed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Confirmed;
    public const Failed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Failed;
    public const Pending = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Pending;
    public const Rejected = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Rejected;
    public const Skipped = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Skipped;
    public const Unchanged = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unchanged;
    public const Unconfirmed = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unconfirmed;
    public const Unverifiable = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unverifiable;
    public const Upgraded = \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Upgraded;

    // Properties
    public protected(set) readonly string $name;

    // Methods
    public function wasContacted( ): bool;

}
```