> CondorcetVote \ [ElephStamp](../../readme.md) \ **DeterministicRandomSource**
# Class DeterministicRandomSource
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Random/DeterministicRandomSource.php#L17)

## Description
A reproducible source, backed by a {@see Randomizer} over a seeded engine.

Not cryptographically secure — intended only for tests and the fake client,
where predictable nonces make proofs deterministic.
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [bytes(...)](method_bytes.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Random\DeterministicRandomSource implements CondorcetVote\ElephStamp\Random\RandomSource
{

    // Methods
    public function __construct( [ string $seed = 'elephstamp' ] );
    public function bytes( int $length ): string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Random\DeterministicRandomSource implements CondorcetVote\ElephStamp\Random\RandomSource
{

    // Properties
    private readonly Random\Randomizer $randomizer;

    // Methods
    public function __construct( [ string $seed = 'elephstamp' ] );
    public function bytes( int $length ): string;

}
```