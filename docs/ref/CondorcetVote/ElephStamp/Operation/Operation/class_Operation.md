> CondorcetVote \ [ElephStamp](../../readme.md) \ **Operation**
# Class Operation
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/Operation.php#L16)

## Description
A single edge in a timestamp proof tree.

An operation takes a message and produces a result, which becomes the
message of the next node in the tree. Operations are immutable value objects.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_MSG_LENGTH | `public const int MAX_MSG_LENGTH = 4096` | _Maximum length of a message an operation may be applied to._ |
| MAX_RESULT_LENGTH | `public const int MAX_RESULT_LENGTH = 4096` | _Maximum length of an operation result._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](method_deserialize.md) | _Read the operation that follows the current cursor position._ |
| [fromTag(...)](method_fromTag.md) | _Build the operation identified by $tag, reading any argument it needs._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [apply(...)](method_apply.md) | _Compute the operation result for the given message._ |
| [comparisonKey(...)](method_comparisonKey.md) | _Key used to order operations deterministically within a timestamp._ |
| [describe(...)](method_describe.md) | _Human-readable label for the operation, used when describing a proof._ |
| [serialize(...)](method_serialize.md) | __ |
| [tag(...)](method_tag.md) | _The one-byte tag identifying this operation in the binary format._ |


## Public Representation
```php
abstract class CondorcetVote\ElephStamp\Operation\Operation
{
    // Constants
    public const int MAX_MSG_LENGTH = 4096;
    public const int MAX_RESULT_LENGTH = 4096;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    final public function apply( string $message ): string;
    public function comparisonKey( ): string;
    abstract public function describe( ): string;
    public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    abstract public function tag( ): string;

}
```

## Full Representation
```php
abstract class CondorcetVote\ElephStamp\Operation\Operation
{
    // Constants
    public const int MAX_MSG_LENGTH = 4096;
    public const int MAX_RESULT_LENGTH = 4096;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    final public function apply( string $message ): string;
    public function comparisonKey( ): string;
    abstract public function describe( ): string;
    public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    abstract public function tag( ): string;

}
```