> CondorcetVote \ [ElephStamp](../../readme.md) \ **TimeAttestation**
# Class TimeAttestation
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Attestation/TimeAttestation.php#L17)

## Description
A leaf of a timestamp proof: evidence attesting that a message existed prior
to some point in time.

The two kinds that matter for this library are {@see \CondorcetVote\ElephStamp\Attestation\PendingAttestation}
(recorded by a calendar, not yet on a blockchain) and
{@see \CondorcetVote\ElephStamp\Attestation\BitcoinAttestation} (committed to the Bitcoin blockchain).
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_PAYLOAD_SIZE | `public const int MAX_PAYLOAD_SIZE = 8192` | _Maximum size of a serialized attestation payload, in bytes._ |
| TAG_SIZE | `public const int TAG_SIZE = 8` | _Length of an attestation tag, in bytes._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](method_deserialize.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [compareTo(...)](method_compareTo.md) | _Order two attestations deterministically._ |
| [describe(...)](method_describe.md) | _Human-readable label for the attestation, used when describing a proof._ |
| [identityKey(...)](method_identityKey.md) | _Stable identity used to deduplicate attestations within a timestamp._ |
| [serialize(...)](method_serialize.md) | __ |
| [tag(...)](method_tag.md) | _The eight-byte tag identifying this attestation type._ |


## Public Representation
```php
abstract class CondorcetVote\ElephStamp\Attestation\TimeAttestation
{
    // Constants
    public const int MAX_PAYLOAD_SIZE = 8192;
    public const int TAG_SIZE = 8;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function compareTo( self $other ): int;
    abstract public function describe( ): string;
    final public function identityKey( ): string;
    final public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    abstract public function tag( ): string;

}
```

## Full Representation
```php
abstract class CondorcetVote\ElephStamp\Attestation\TimeAttestation
{
    // Constants
    public const int MAX_PAYLOAD_SIZE = 8192;
    public const int TAG_SIZE = 8;

    // Static Methods
    public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function compareTo( self $other ): int;
    abstract public function describe( ): string;
    final public function identityKey( ): string;
    final public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    abstract public function tag( ): string;

}
```