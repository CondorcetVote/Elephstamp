> CondorcetVote \ [ElephStamp](../../readme.md) \ **Sha256**
# Class Sha256
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/Sha256.php#L10)

## Description
SHA-256, the default and recommended hash operation.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_MSG_LENGTH | `public const int MAX_MSG_LENGTH = 4096` | _Maximum length of a message an operation may be applied to._ |
| MAX_RESULT_LENGTH | `public const int MAX_RESULT_LENGTH = 4096` | _Maximum length of an operation result._ |
| TAG | `public const string TAG = ''` | __ |

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
| [describe(...)](method_describe.md) | __ |
| [digestLength(...)](method_digestLength.md) | __ |
| [hashChunks(...)](../NativeHashOperation/method_hashChunks.md) | __ |
| [hashData(...)](../NativeHashOperation/method_hashData.md) | __ |
| [hashStream(...)](../NativeHashOperation/method_hashStream.md) | __ |
| [isComputable(...)](../Operation/method_isComputable.md) | _Whether this library can compute the operation's result._ |
| [serialize(...)](../Operation/method_serialize.md) | __ |
| [tag(...)](method_tag.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Operation\Sha256 extends CondorcetVote\ElephStamp\Operation\NativeHashOperation
{
    // Constants
    public const string TAG = '';
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
    public function describe( ): string;
    public function digestLength( ): int;
    public function tag( ): string;

    // Inherited Methods
    final public function Operation->apply( string $message ): string;
    public function Operation->comparisonKey( ): string;
    final public function NativeHashOperation->hashChunks( iterable $chunks ): string;
    final public function NativeHashOperation->hashData( string $data ): string;
    final public function NativeHashOperation->hashStream( $stream ): string;
    public function Operation->isComputable( ): bool;
    public function Operation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Operation\Sha256 extends CondorcetVote\ElephStamp\Operation\NativeHashOperation
{
    // Constants
    public const string TAG = '';
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
    public function describe( ): string;
    public function digestLength( ): int;
    public function tag( ): string;

    // Inherited Methods
    final public function Operation->apply( string $message ): string;
    public function Operation->comparisonKey( ): string;
    final public static function Operation::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function HashOperation::deserializeHash( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public static function HashOperation::fromName( string $name ): self;
    final public static function Operation::fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public function NativeHashOperation->hashChunks( iterable $chunks ): string;
    final public function NativeHashOperation->hashData( string $data ): string;
    final public function NativeHashOperation->hashStream( $stream ): string;
    public function Operation->isComputable( ): bool;
    public static function HashOperation::names( ): array;
    public function Operation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```