> CondorcetVote \ [ElephStamp](../../readme.md) \ **VerificationReport**
# Class VerificationReport
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/VerificationReport.php#L13)

## Description
The result of {@see Verifier::verify()}: the file check, one entry per
Bitcoin attestation, and the conclusion drawn from them.
## Elements

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [anchors(...)](property_anchors.md) | __ |
| [fileMatches(...)](property_fileMatches.md) | __ |
| [requiredConfirmations(...)](property_requiredConfirmations.md) | __ |
| [source(...)](property_source.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [attestedAt(...)](method_attestedAt.md) | _The date the file is proven to have existed before: the time of the earliest verifying block. Null unless verified._ |
| [attestingAnchor(...)](method_attestingAnchor.md) | _The earliest block that verifies the proof, i.e. the one whose time is the attested date. Null unless the verdict is {@see Verdict::Verified}._ |
| [count(...)](method_count.md) | __ |
| [filter(...)](method_filter.md) | __ |
| [isVerified(...)](method_isVerified.md) | __ |
| [mismatches(...)](method_mismatches.md) | _The attestations whose block does not commit to the proof, whatever the verdict. Worth reporting even on a verified proof: one of its calendars handed out something wrong._ |
| [verdict(...)](method_verdict.md) | _The conclusion drawn from the file check and the attestations._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Verify\VerificationReport
{

    // Properties
    public protected(set) readonly array $anchors;
    public protected(set) readonly ?bool $fileMatches;
    public protected(set) readonly int $requiredConfirmations;
    public protected(set) readonly string $source;

    // Methods
    public function __construct( ?bool $fileMatches, array $anchors, int $requiredConfirmations, string $source );
    public function attestedAt( ): ?DateTimeImmutable;
    public function attestingAnchor( ): ?CondorcetVote\ElephStamp\Verify\AnchorVerification;
    public function count( CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome ): int;
    public function filter( CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome ): array;
    public function isVerified( ): bool;
    public function mismatches( ): array;
    public function verdict( ): CondorcetVote\ElephStamp\Verify\Verdict;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Verify\VerificationReport
{

    // Properties
    public protected(set) readonly array $anchors;
    public protected(set) readonly ?bool $fileMatches;
    public protected(set) readonly int $requiredConfirmations;
    public protected(set) readonly string $source;

    // Methods
    public function __construct( ?bool $fileMatches, array $anchors, int $requiredConfirmations, string $source );
    public function attestedAt( ): ?DateTimeImmutable;
    public function attestingAnchor( ): ?CondorcetVote\ElephStamp\Verify\AnchorVerification;
    public function count( CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome ): int;
    public function filter( CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome ): array;
    public function isVerified( ): bool;
    public function mismatches( ): array;
    public function verdict( ): CondorcetVote\ElephStamp\Verify\Verdict;

}
```