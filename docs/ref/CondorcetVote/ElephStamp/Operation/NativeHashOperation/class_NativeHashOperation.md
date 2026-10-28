> CondorcetVote \ [ElephStamp](../../readme.md) \ **NativeHashOperation**
# Class NativeHashOperation
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/NativeHashOperation.php#L13)

## Description
A hash operation computed by PHP's {@see hash()} extension.

These hash incrementally, so a file of any size is digested in bounded
memory.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_MSG_LENGTH | `public const int MAX_MSG_LENGTH = 4096` | _Maximum length of a message an operation may be applied to._ |
| MAX_RESULT_LENGTH | `public const int MAX_RESULT_LENGTH = 4096` | _Maximum length of an operation result._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](../Operation/method_deserialize.md) | _Read the operation that follows the current cursor position._ |
| [deserializeHash(...)](../HashOperation/method_deserializeHash.md) | _Read a cryptographic hash operation; reject non-hash operations._ |
| [fromName(...)](../HashOperation/method_fromName.md) | _The hash operation with the given name, as {@see describe()} returns it._ |
| [fromTag(...)](../Operation/method_fromTag.md) | _Build the operation identified by $tag, reading any argument it needs._ |
| [names(...)](../HashOperation/method_names.md) | _The hash operations a detached timestamp can be built with, by name as {@see describe()} returns it; the default one first._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [apply(...)](../Operation/method_apply.md) | _Compute the operation result for the given message._ |
| [comparisonKey(...)](../Operation/method_comparisonKey.md) | _Key used to order operations deterministically within a timestamp._ |
| [describe(...)](../Operation/method_describe.md) | _Human-readable label for the operation, used when describing a proof._ |
| [digestLength(...)](../HashOperation/method_digestLength.md) | _Length of the digest this operation produces, in bytes._ |
| [hashChunks(...)](method_hashChunks.md) | __ |
| [hashData(...)](method_hashData.md) | __ |
| [hashStream(...)](method_hashStream.md) | __ |
| [isComputable(...)](../Operation/method_isComputable.md) | _Whether this library can compute the operation's result._ |
| [serialize(...)](../Operation/method_serialize.md) | __ |
| [tag(...)](../Operation/method_tag.md) | _The one-byte tag identifying this operation in the binary format._ |


## Public Representation
```php
abstract class CondorcetVote\ElephStamp\Operation\NativeHashOperation extends CondorcetVote\ElephStamp\Operation\HashOperation
{
    // Inherited Constants
    public const int Operation::MAX_MSG_LENGTH = 4096;
    public const int Operation::MAX_RESULT_LENGTH = 4096;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function deserializeHash( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function fromName( string $name ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function names( ): array;

    // Methods
    final public function hashChunks( iterable $chunks ): string;
    final public function hashData( string $data ): string;
    final public function hashStream( $stream ): string;

    // Inherited Methods
    final public function Operation->apply( string $message ): string;
    public function Operation->comparisonKey( ): string;
    abstract public function Operation->describe( ): string;
    abstract public function HashOperation->digestLength( ): int;
    public function Operation->isComputable( ): bool;
    public function Operation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    abstract public function Operation->tag( ): string;

}
```

## Full Representation
```php
abstract class CondorcetVote\ElephStamp\Operation\NativeHashOperation extends CondorcetVote\ElephStamp\Operation\HashOperation
{
    // Inherited Constants
    public const int Operation::MAX_MSG_LENGTH = 4096;
    public const int Operation::MAX_RESULT_LENGTH = 4096;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function deserializeHash( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function fromName( string $name ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function names( ): array;

    // Methods
    final public function hashChunks( iterable $chunks ): string;
    final public function hashData( string $data ): string;
    final public function hashStream( $stream ): string;

    // Inherited Methods
    final public function Operation->apply( string $message ): string;
    public function Operation->comparisonKey( ): string;
    abstract public function Operation->describe( ): string;
    final public static function Operation::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function HashOperation::deserializeHash( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    abstract public function HashOperation->digestLength( ): int;
    public static function HashOperation::fromName( string $name ): self;
    final public static function Operation::fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public function Operation->isComputable( ): bool;
    public static function HashOperation::names( ): array;
    public function Operation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    abstract public function Operation->tag( ): string;

}
```