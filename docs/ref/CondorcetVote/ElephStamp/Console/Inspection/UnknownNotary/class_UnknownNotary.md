> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **UnknownNotary**
# Class UnknownNotary
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Inspection/UnknownNotary.php#L10)

## Description
An attestation of a type this library does not understand.
## Elements

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [payloadLength(...)](property_payloadLength.md) | __ |
| [tag(...)](property_tag.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [tagHex(...)](method_tagHex.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\UnknownNotary
{

    // Properties
    public protected(set) readonly int $payloadLength;
    public protected(set) readonly string $tag;

    // Methods
    public function __construct( string $tag, int $payloadLength );
    public function tagHex( ): string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Inspection\UnknownNotary
{

    // Properties
    public protected(set) readonly int $payloadLength;
    public protected(set) readonly string $tag;

    // Methods
    public function __construct( string $tag, int $payloadLength );
    public function tagHex( ): string;

}
```