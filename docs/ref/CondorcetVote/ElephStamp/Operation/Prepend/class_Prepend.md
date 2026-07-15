> CondorcetVote \ [ElephStamp](../../readme.md) \ **Prepend**
# Class Prepend
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/Prepend.php#L10)

## Description
Prepend a fixed prefix to the message.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_MSG_LENGTH | `public const int MAX_MSG_LENGTH = 4096` | _Maximum length of a message an operation may be applied to._ |
| MAX_RESULT_LENGTH | `public const int MAX_RESULT_LENGTH = 4096` | _Maximum length of an operation result._ |
| TAG | `public const string TAG = 'ñ'` | __ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [deserialize(...)](../Operation/method_deserialize.md) | _Read the operation that follows the current cursor position._ |
| [fromTag(...)](../Operation/method_fromTag.md) | _Build the operation identified by $tag, reading any argument it needs._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [argument(...)](../BinaryOperation/property_argument.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](../BinaryOperation/method___construct.md) | __ |
| [apply(...)](../Operation/method_apply.md) | _Compute the operation result for the given message._ |
| [comparisonKey(...)](../BinaryOperation/method_comparisonKey.md) | __ |
| [describe(...)](method_describe.md) | __ |
| [serialize(...)](../BinaryOperation/method_serialize.md) | __ |
| [tag(...)](method_tag.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Operation\Prepend extends CondorcetVote\ElephStamp\Operation\BinaryOperation
{
    // Constants
    public const string TAG = 'ñ';
    // Inherited Constants
    public const int Operation::MAX_MSG_LENGTH = 4096;
    public const int Operation::MAX_RESULT_LENGTH = 4096;

    // Inherited Properties
    public protected(set) readonly string BinaryOperation->argument;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function describe( ): string;
    public function tag( ): string;

    // Inherited Methods
    public function BinaryOperation->__construct( string $argument );
    final public function Operation->apply( string $message ): string;
    public function BinaryOperation->comparisonKey( ): string;
    public function BinaryOperation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Operation\Prepend extends CondorcetVote\ElephStamp\Operation\BinaryOperation
{
    // Constants
    public const string TAG = 'ñ';
    // Inherited Constants
    public const int Operation::MAX_MSG_LENGTH = 4096;
    public const int Operation::MAX_RESULT_LENGTH = 4096;

    // Inherited Properties
    public protected(set) readonly string BinaryOperation->argument;

    // Static Methods
    final public static function deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;

    // Methods
    public function describe( ): string;
    public function tag( ): string;

    // Inherited Methods
    public function BinaryOperation->__construct( string $argument );
    final public function Operation->apply( string $message ): string;
    public function BinaryOperation->comparisonKey( ): string;
    final public static function Operation::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    final public static function Operation::fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self;
    public function BinaryOperation->serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;

}
```