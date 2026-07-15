> CondorcetVote \ [ElephStamp](../../readme.md) \ **BinaryOperation**
# Class BinaryOperation
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/BinaryOperation.php#L13)

## Description
An operation that combines the message with a fixed argument (append/prepend).
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
| [fromTag(...)](../Operation/method_fromTag.md) | _Build the operation identified by $tag, reading any argument it needs._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [argument(...)](property_argument.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [apply(...)](../Operation/method_apply.md) | _Compute the operation result for the given message._ |
| [comparisonKey(...)](method_comparisonKey.md) | __ |
| [describe(...)](../Operation/method_describe.md) | _Human-readable label for the operation, used when describing a proof._ |
| [serialize(...)](method_serialize.md) | __ |
| [tag(...)](../Operation/method_tag.md) | _The one-byte tag identifying this operation in the binary format._ |


## Public Representation
```php
abstract class CondorcetVote\ElephStamp\Operation\BinaryOperation extends CondorcetVote\ElephStamp\Operation\Operation
{
    // Inherited Constants
    public const int Operation::MAX_MSG_LENGTH = 4096;
    public const int Operation::MAX_RESULT_LENGTH = 4096;

    // Properties
    public protected(set) readonly string $argument;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function __construct( string $argument );
    public function comparisonKey( ): string;
    public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

    // Inherited Methods
    final public function Operation->apply( string $message ): string;
    abstract public function Operation->describe( ): string;
    abstract public function Operation->tag( ): string;

}
```

## Full Representation
```php
abstract class CondorcetVote\ElephStamp\Operation\BinaryOperation extends CondorcetVote\ElephStamp\Operation\Operation
{
    // Inherited Constants
    public const int Operation::MAX_MSG_LENGTH = 4096;
    public const int Operation::MAX_RESULT_LENGTH = 4096;

    // Properties
    public protected(set) readonly string $argument;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function __construct( string $argument );
    public function comparisonKey( ): string;
    public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

    // Inherited Methods
    final public function Operation->apply( string $message ): string;
    abstract public function Operation->describe( ): string;
    final public static function Operation::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function Operation::fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    abstract public function Operation->tag( ): string;

}
```