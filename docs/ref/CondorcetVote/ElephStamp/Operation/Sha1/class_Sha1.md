> CondorcetVote \ [ElephStamp](../../readme.md) \ **Sha1**
# Class Sha1
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/Sha1.php#L14)

## Description
SHA-1.

Collision-prone hashes are still secure for timestamping: a collision only
proves two messages existed before a point in time, which is exactly the
claim a timestamp makes.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_MSG_LENGTH | `public const int MAX_MSG_LENGTH = 4096` | _Maximum length of a message an operation may be applied to._ |
| MAX_RESULT_LENGTH | `public const int MAX_RESULT_LENGTH = 4096` | _Maximum length of an operation result._ |
| TAG | `public const string TAG = ''` | __ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](../Operation/method_deserialize.md) | _Read the operation that follows the current cursor position._ |
| [deserializeHash(...)](../HashOperation/method_deserializeHash.md) | _Read a cryptographic hash operation; reject non-hash operations._ |
| [fromTag(...)](../Operation/method_fromTag.md) | _Build the operation identified by $tag, reading any argument it needs._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [apply(...)](../Operation/method_apply.md) | _Compute the operation result for the given message._ |
| [comparisonKey(...)](../Operation/method_comparisonKey.md) | _Key used to order operations deterministically within a timestamp._ |
| [describe(...)](method_describe.md) | __ |
| [digestLength(...)](method_digestLength.md) | __ |
| [hashChunks(...)](../HashOperation/method_hashChunks.md) | _Hash a sequence of chunks incrementally, in bounded memory._ |
| [hashData(...)](../HashOperation/method_hashData.md) | _Hash a whole in-memory payload._ |
| [hashStream(...)](../HashOperation/method_hashStream.md) | _Hash a stream from its current position to its end, in bounded memory._ |
| [isComputable(...)](../Operation/method_isComputable.md) | _Whether this library can compute the operation's result._ |
| [serialize(...)](../Operation/method_serialize.md) | __ |
| [tag(...)](method_tag.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Operation\Sha1 extends CondorcetVote\ElephStamp\Operation\HashOperation
{
    // Constants
    public const string TAG = '';
    // Inherited Constants
    public const int Operation::MAX_MSG_LENGTH = 4096;
    public const int Operation::MAX_RESULT_LENGTH = 4096;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function deserializeHash( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function describe( ): string;
    public function digestLength( ): int;
    public function tag( ): string;

    // Inherited Methods
    final public function Operation->apply( string $message ): string;
    public function Operation->comparisonKey( ): string;
    final public function HashOperation->hashChunks( iterable $chunks ): string;
    final public function HashOperation->hashData( string $data ): string;
    final public function HashOperation->hashStream( $stream ): string;
    public function Operation->isComputable( ): bool;
    public function Operation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Operation\Sha1 extends CondorcetVote\ElephStamp\Operation\HashOperation
{
    // Constants
    public const string TAG = '';
    // Inherited Constants
    public const int Operation::MAX_MSG_LENGTH = 4096;
    public const int Operation::MAX_RESULT_LENGTH = 4096;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function deserializeHash( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function describe( ): string;
    public function digestLength( ): int;
    public function tag( ): string;

    // Inherited Methods
    final public function Operation->apply( string $message ): string;
    public function Operation->comparisonKey( ): string;
    final public static function Operation::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function HashOperation::deserializeHash( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function Operation::fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public function HashOperation->hashChunks( iterable $chunks ): string;
    final public function HashOperation->hashData( string $data ): string;
    final public function HashOperation->hashStream( $stream ): string;
    public function Operation->isComputable( ): bool;
    public function Operation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```