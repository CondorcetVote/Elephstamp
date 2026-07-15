> CondorcetVote \ [ElephStamp](../../readme.md) \ **CryptoRandomSource**
# Class CryptoRandomSource
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Random/CryptoRandomSource.php#L15)

## Description
The default source, backed by a {@see Randomizer} over the cryptographically
secure engine.
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [bytes(...)](method_bytes.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Random\CryptoRandomSource implements CondorcetVote\ElephStamp\Random\RandomSource
{

    // Methods
    public function __construct( );
    public function bytes( int $length ): string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Random\CryptoRandomSource implements CondorcetVote\ElephStamp\Random\RandomSource
{

    // Properties
    private readonly Random\Randomizer $randomizer;

    // Methods
    public function __construct( );
    public function bytes( int $length ): string;

}
```