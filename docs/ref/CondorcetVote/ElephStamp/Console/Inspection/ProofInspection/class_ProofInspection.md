> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **ProofInspection**
# Class ProofInspection
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Inspection/ProofInspection.php#L13)

## Description
Everything the CLI reports about a proof, extracted once from a {@see Receipt}.
## Elements

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [anchors(...)](property_anchors.md) | __ |
| [receipt(...)](property_receipt.md) | __ |
| [sizeInBytes(...)](property_sizeInBytes.md) | __ |
| [submissions(...)](property_submissions.md) | __ |
| [unknownNotaries(...)](property_unknownNotaries.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [bitcoinBlockHeight(...)](method_bitcoinBlockHeight.md) | _The block height the proof claims, when complete._ |
| [pendingSubmissions(...)](method_pendingSubmissions.md) | _Submissions the calendar has not confirmed yet._ |
| [status(...)](method_status.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\ProofInspection
{

    // Properties
    public protected(set) readonly array $anchors;
    public protected(set) readonly CondorcetVote\ElephStamp\Receipt $receipt;
    public protected(set) readonly int $sizeInBytes;
    public protected(set) readonly array $submissions;
    public protected(set) readonly array $unknownNotaries;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Receipt $receipt, array $submissions, array $anchors, array $unknownNotaries, int $sizeInBytes );
    public function bitcoinBlockHeight( ): ?int;
    public function pendingSubmissions( ): array;
    public function status( ): CondorcetVote\ElephStamp\Status;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\ProofInspection
{

    // Properties
    public protected(set) readonly array $anchors;
    public protected(set) readonly CondorcetVote\ElephStamp\Receipt $receipt;
    public protected(set) readonly int $sizeInBytes;
    public protected(set) readonly array $submissions;
    public protected(set) readonly array $unknownNotaries;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Receipt $receipt, array $submissions, array $anchors, array $unknownNotaries, int $sizeInBytes );
    public function bitcoinBlockHeight( ): ?int;
    public function pendingSubmissions( ): array;
    public function status( ): CondorcetVote\ElephStamp\Status;

}
```