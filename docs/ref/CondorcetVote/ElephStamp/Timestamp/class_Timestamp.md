> CondorcetVote \ **Timestamp**
# Class Timestamp
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L19)

## Description
A proof that one or more attestations commit to a message.

The proof is a tree: each node is a message, each edge an {@see \CondorcetVote\ElephStamp\Operation\Operation}
acting on it, and the leaves are {@see \CondorcetVote\ElephStamp\Attestation\TimeAttestation}s. This class is the
mutable heart of the format — merging calendar responses grows the tree.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| RECURSION_LIMIT | `private const int RECURSION_LIMIT = 256` | __ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](method_deserialize.md) | _Deserialize a timestamp for a known initial message._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [msg(...)](property_msg.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [addAttestation(...)](method_addAttestation.md) | _Attach an attestation to this node (deduplicated)._ |
| [addOp(...)](method_addOp.md) | _Add an operation edge, returning the timestamp of its result._ |
| [allAttestations(...)](method_allAttestations.md) | _Every attestation in the tree, paired with the message it commits to._ |
| [attestations(...)](method_attestations.md) | __ |
| [describe(...)](method_describe.md) | _Render the proof tree as an indented, human-readable string._ |
| [findPending(...)](method_findPending.md) | _The shallowest nodes that carry a pending attestation._ |
| [hasBitcoinAttestation(...)](method_hasBitcoinAttestation.md) | _Whether the tree contains a Bitcoin attestation, i.e. is complete._ |
| [merge(...)](method_merge.md) | _Merge every operation and attestation from another timestamp into this one._ |
| [operations(...)](method_operations.md) | __ |
| [serialize(...)](method_serialize.md) | __ |
| [setOp(...)](method_setOp.md) | _Bind a specific child timestamp to an operation edge._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Timestamp
{

    // Properties
    public protected(set) readonly string $msg;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer, string $initialMsg, [ int $recursionLimit = 256 ] ): self;

    // Methods
    public function __construct( string $msg );
    public function addAttestation( CondorcetVote\ElephStamp\Attestation\TimeAttestation $attestation ): void;
    public function addOp( CondorcetVote\ElephStamp\Operation\Operation $operation ): self;
    public function allAttestations( ): array;
    public function attestations( ): array;
    public function describe( [ int $indent = 0 ] ): string;
    public function findPending( ): array;
    public function hasBitcoinAttestation( ): bool;
    public function merge( self $other ): void;
    public function operations( ): array;
    public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    public function setOp( CondorcetVote\ElephStamp\Operation\Operation $operation, self $child ): void;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Timestamp
{
    // Constants
    private const int RECURSION_LIMIT = 256;

    // Properties
    public protected(set) readonly string $msg;
    private array $attestations = [];
    private array $ops = [];

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer, string $initialMsg, [ int $recursionLimit = 256 ] ): self;

    // Methods
    public function __construct( string $msg );
    public function addAttestation( CondorcetVote\ElephStamp\Attestation\TimeAttestation $attestation ): void;
    public function addOp( CondorcetVote\ElephStamp\Operation\Operation $operation ): self;
    public function allAttestations( ): array;
    public function attestations( ): array;
    public function describe( [ int $indent = 0 ] ): string;
    public function findPending( ): array;
    public function hasBitcoinAttestation( ): bool;
    public function merge( self $other ): void;
    public function operations( ): array;
    public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    public function setOp( CondorcetVote\ElephStamp\Operation\Operation $operation, self $child ): void;

}
```