> CondorcetVote \ **Status**
# Enum Status
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Status.php#L10)

## Description
Lifecycle state of a {@see Receipt}.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| Complete | `public const Complete = \CondorcetVote\ElephStamp\Status::Complete` | _Confirmed by the Bitcoin blockchain; the proof is final._ |
| Pending | `public const Pending = \CondorcetVote\ElephStamp\Status::Pending` | _Recorded by a calendar, awaiting confirmation on the Bitcoin blockchain._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [name(...)](property_name.md) | __ |


## Public Representation
```php
enum CondorcetVote\ElephStamp\Status implements UnitEnum
{
    case Pending;
    case Complete;
    // Constants
    public const Complete = \CondorcetVote\ElephStamp\Status::Complete;
    public const Pending = \CondorcetVote\ElephStamp\Status::Pending;

    // Properties
    public protected(set) readonly string $name;

}
```

## Full Representation
```php
enum CondorcetVote\ElephStamp\Status implements UnitEnum
{
    case Pending;
    case Complete;
    // Constants
    public const Complete = \CondorcetVote\ElephStamp\Status::Complete;
    public const Pending = \CondorcetVote\ElephStamp\Status::Pending;

    // Properties
    public protected(set) readonly string $name;

}
```