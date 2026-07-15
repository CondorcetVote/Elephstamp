> CondorcetVote \ [ElephStamp](../../readme.md) \ **PendingAttestation**
# Class PendingAttestation
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Attestation/PendingAttestation.php#L17)

## Description
A commitment recorded by a remote calendar, awaiting confirmation.

The URI points at the calendar that promised to include the commitment in a
future blockchain attestation; it is where an upgrade request is sent to
fetch the completed proof.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| ALLOWED_URI_CHARS | `public const string ALLOWED_URI_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._/:'` | _Characters permitted in a calendar URI._ |
| MAX_PAYLOAD_SIZE | `public const int MAX_PAYLOAD_SIZE = 8192` | _Maximum size of a serialized attestation payload, in bytes._ |
| MAX_URI_LENGTH | `public const int MAX_URI_LENGTH = 1000` | __ |
| TAG | `public const string TAG = 'ƒίγ.ω'` | __ |
| TAG_SIZE | `public const int TAG_SIZE = 8` | _Length of an attestation tag, in bytes._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](../TimeAttestation/method_deserialize.md) | __ |
| [deserializePayload(...)](method_deserializePayload.md) | __ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [uri(...)](property_uri.md) | __ |

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
final class CondorcetVote\ElephStamp\Attestation\PendingAttestation extends CondorcetVote\ElephStamp\Attestation\TimeAttestation
{
    // Constants
    public const string ALLOWED_URI_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._/:';
    public const int MAX_URI_LENGTH = 1000;
    public const string TAG = 'ƒίγ.ω';
    // Inherited Constants
    public const int TimeAttestation::MAX_PAYLOAD_SIZE = 8192;
    public const int TimeAttestation::TAG_SIZE = 8;

    // Properties
    public protected(set) readonly string $uri;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function deserializePayload( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function __construct( string $uri );
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
final class CondorcetVote\ElephStamp\Attestation\PendingAttestation extends CondorcetVote\ElephStamp\Attestation\TimeAttestation
{
    // Constants
    public const string ALLOWED_URI_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._/:';
    public const int MAX_URI_LENGTH = 1000;
    public const string TAG = 'ƒίγ.ω';
    // Inherited Constants
    public const int TimeAttestation::MAX_PAYLOAD_SIZE = 8192;
    public const int TimeAttestation::TAG_SIZE = 8;

    // Properties
    public protected(set) readonly string $uri;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function deserializePayload( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    private static function checkUri( string $uri ): void;

    // Methods
    public function __construct( string $uri );
    public function compareTo( parent $other ): int;
    public function describe( ): string;
    public function tag( ): string;

    // Inherited Methods
    public static function TimeAttestation::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public function TimeAttestation->identityKey( ): string;
    final public function TimeAttestation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```