> CondorcetVote \ [ElephStamp](../../readme.md) \ **UnknownAttestation**
# Class UnknownAttestation
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Attestation/UnknownAttestation.php#L17)

## Description
An attestation whose type this library does not understand (for example
Litecoin or Ethereum).

Its raw payload is preserved verbatim so proofs round-trip losslessly, but
no meaning is attached to it.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_PAYLOAD_SIZE | `public const int MAX_PAYLOAD_SIZE = 8192` | _Maximum size of a serialized attestation payload, in bytes._ |
| TAG_SIZE | `public const int TAG_SIZE = 8` | _Length of an attestation tag, in bytes._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](../TimeAttestation/method_deserialize.md) | __ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [payload(...)](property_payload.md) | __ |

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
final class CondorcetVote\ElephStamp\Attestation\UnknownAttestation extends CondorcetVote\ElephStamp\Attestation\TimeAttestation
{
    // Inherited Constants
    public const int TimeAttestation::MAX_PAYLOAD_SIZE = 8192;
    public const int TimeAttestation::TAG_SIZE = 8;

    // Properties
    public protected(set) readonly string $payload;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function __construct( string $tag, string $payload );
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
final class CondorcetVote\ElephStamp\Attestation\UnknownAttestation extends CondorcetVote\ElephStamp\Attestation\TimeAttestation
{
    // Inherited Constants
    public const int TimeAttestation::MAX_PAYLOAD_SIZE = 8192;
    public const int TimeAttestation::TAG_SIZE = 8;

    // Properties
    public protected(set) readonly string $payload;
    private readonly string $tag;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function __construct( string $tag, string $payload );
    public function compareTo( parent $other ): int;
    public function describe( ): string;
    public function tag( ): string;

    // Inherited Methods
    public static function TimeAttestation::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public function TimeAttestation->identityKey( ): string;
    final public function TimeAttestation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```