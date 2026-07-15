> CondorcetVote \ [ElephStamp](../../readme.md) \ **BitcoinAttestation**
# Class BitcoinAttestation
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Attestation/BitcoinAttestation.php#L19)

## Description
A commitment confirmed by the Bitcoin blockchain.

The presence of this attestation means the timestamp is complete: the
committed digest is the merkle root of the block at the recorded height.

This library does not verify the attestation against the blockchain; it only
reports the height so callers can look it up with the tool of their choice.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_PAYLOAD_SIZE | `public const int MAX_PAYLOAD_SIZE = 8192` | _Maximum size of a serialized attestation payload, in bytes._ |
| TAG | `public const string TAG = 'ˆ–s×'` | __ |
| TAG_SIZE | `public const int TAG_SIZE = 8` | _Length of an attestation tag, in bytes._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](../TimeAttestation/method_deserialize.md) | __ |
| [deserializePayload(...)](method_deserializePayload.md) | __ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [blockHeight(...)](property_blockHeight.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [compareTo(...)](method_compareTo.md) | __ |
| [describe(...)](method_describe.md) | __ |
| [identityKey(...)](../TimeAttestation/method_identityKey.md) | _Stable identity used to deduplicate attestations within a timestamp._ |
| [serialize(...)](../TimeAttestation/method_serialize.md) | __ |
| [tag(...)](method_tag.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Attestation\BitcoinAttestation extends CondorcetVote\ElephStamp\Attestation\TimeAttestation
{
    // Constants
    public const string TAG = 'ˆ–s×';
    // Inherited Constants
    public const int TimeAttestation::MAX_PAYLOAD_SIZE = 8192;
    public const int TimeAttestation::TAG_SIZE = 8;

    // Properties
    public protected(set) readonly int $blockHeight;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function deserializePayload( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function __construct( int $blockHeight );
    public function compareTo( parent $other ): int;
    public function describe( ): string;
    public function tag( ): string;

    // Inherited Methods
    final public function TimeAttestation->identityKey( ): string;
    final public function TimeAttestation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Attestation\BitcoinAttestation extends CondorcetVote\ElephStamp\Attestation\TimeAttestation
{
    // Constants
    public const string TAG = 'ˆ–s×';
    // Inherited Constants
    public const int TimeAttestation::MAX_PAYLOAD_SIZE = 8192;
    public const int TimeAttestation::TAG_SIZE = 8;

    // Properties
    public protected(set) readonly int $blockHeight;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function deserializePayload( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function __construct( int $blockHeight );
    public function compareTo( parent $other ): int;
    public function describe( ): string;
    public function tag( ): string;

    // Inherited Methods
    public static function TimeAttestation::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public function TimeAttestation->identityKey( ): string;
    final public function TimeAttestation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```