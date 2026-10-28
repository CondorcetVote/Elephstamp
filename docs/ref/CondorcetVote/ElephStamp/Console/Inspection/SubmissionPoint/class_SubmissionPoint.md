> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **SubmissionPoint**
# Class SubmissionPoint
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Inspection/SubmissionPoint.php#L22)

## Description
Where, in a proof tree, the client handed its digest over to the calendars.

A proof does not record that point explicitly. It is inferred from the
layout: everything the client computes (privacy nonce, batch merkle tree)
is one linear chain from the file digest, and every calendar grafts its own
branch onto the digest it received. The first fork of the tree, or a node
several calendars attested directly, is therefore the submitted digest. A
proof with a single branch (one calendar, or a reference proof collapsed to
its bitcoin path) has no such point that can be told apart from the
calendar's own operations: {@see \CondorcetVote\ElephStamp\Console\Inspection\locate()} then returns null rather than guess.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| NONCE_LENGTH | `private const int NONCE_LENGTH = 16` | _Length of the privacy nonce the reference client and ElephStamp append to the file digest._ |
| NONCE_STEP_LENGTH | `private const int NONCE_STEP_LENGTH = 2` | _Operations of the nonce step: the append, then the hash._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [locate(...)](method_locate.md) | _Find the submission point below a proof's root (the file digest node)._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [clientOperations(...)](property_clientOperations.md) | __ |
| [node(...)](property_node.md) | __ |
| [nonce(...)](property_nonce.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [digest(...)](method_digest.md) | _The submitted digest, or null below an operation that cannot be computed._ |
| [digestHex(...)](method_digestHex.md) | __ |
| [isBatchRoot(...)](method_isBatchRoot.md) | _Whether the submitted digest is the root of a merkle tree binding several files._ |
| [isFileDigest(...)](method_isFileDigest.md) | _Whether the file digest itself went to the calendars: no nonce, no batch._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\SubmissionPoint
{

    // Properties
    public protected(set) readonly array $clientOperations;
    public protected(set) readonly CondorcetVote\ElephStamp\Timestamp $node;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Operation\Append $nonce;

    // Static Methods
    public static function locate( CondorcetVote\ElephStamp\Timestamp $root ): ?self;

    // Methods
    public function digest( ): ?string;
    public function digestHex( ): ?string;
    public function isBatchRoot( ): bool;
    public function isFileDigest( ): bool;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\SubmissionPoint
{
    // Constants
    private const int NONCE_LENGTH = 16;
    private const int NONCE_STEP_LENGTH = 2;

    // Properties
    public protected(set) readonly array $clientOperations;
    public protected(set) readonly CondorcetVote\ElephStamp\Timestamp $node;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Operation\Append $nonce;

    // Static Methods
    public static function locate( CondorcetVote\ElephStamp\Timestamp $root ): ?self;

    // Methods
    public function digest( ): ?string;
    public function digestHex( ): ?string;
    public function isBatchRoot( ): bool;
    public function isFileDigest( ): bool;

}
```