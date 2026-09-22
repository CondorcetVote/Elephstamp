> CondorcetVote \ [ElephStamp](../../readme.md) \ **Verifier**
# Class Verifier
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/Verifier.php#L20)

## Description
Checks a receipt against the Bitcoin blockchain, through a {@see BlockHeaderSource}.

Everything inside the proof is recomputed locally: file digest, operations,
transaction, merkle branch. The only external question is "what is the
merkle root of block N?", asked to the source for each Bitcoin attestation.
How much the answer can be trusted depends on the source: a public
explorer is a third party, a node you run is not.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| DEFAULT_REQUIRED_CONFIRMATIONS | `public const int DEFAULT_REQUIRED_CONFIRMATIONS = 6` | _Blocks a verifying block must be buried under, itself included, before the attestation counts as final._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [checkAnchors(...)](method_checkAnchors.md) | _Check Bitcoin attestations against the blocks they name, whatever proof they come from: a receipt, or a calendar's answer about to be merged into one._ |
| [verify(...)](method_verify.md) | _Verify a receipt, and optionally that it is the proof of a given file._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Verify\Verifier
{
    // Constants
    public const int DEFAULT_REQUIRED_CONFIRMATIONS = 6;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Verify\BlockHeaderSource $source, [ int $requiredConfirmations = 6 ] );
    public function checkAnchors( array $anchors ): array;
    public function verify( CondorcetVote\ElephStamp\Receipt $receipt, [ ?CondorcetVote\ElephStamp\FileToStamp $file = null ] ): CondorcetVote\ElephStamp\Verify\VerificationReport;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Verify\Verifier
{
    // Constants
    public const int DEFAULT_REQUIRED_CONFIRMATIONS = 6;

    // Properties
    private readonly int $requiredConfirmations;
    private readonly CondorcetVote\ElephStamp\Verify\BlockHeaderSource $source;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Verify\BlockHeaderSource $source, [ int $requiredConfirmations = 6 ] );
    public function checkAnchors( array $anchors ): array;
    public function verify( CondorcetVote\ElephStamp\Receipt $receipt, [ ?CondorcetVote\ElephStamp\FileToStamp $file = null ] ): CondorcetVote\ElephStamp\Verify\VerificationReport;

}
```