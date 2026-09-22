> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **StampCommand**
# Class StampCommand
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Command/StampCommand.php#L40)
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [__invoke(...)](method___invoke.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\StampCommand
{

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory );
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, [ array $files = [], ?string $outputPath = null, ?string $digest = null, bool $noNonce = false, array $calendar = [], ?int $required = null, ?float $timeout = null, bool $force = false ] ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\StampCommand
{

    // Properties
    private readonly CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory;

    // Static Methods
    private static function fromDigest( string $digest, ?string $outputPath ): array;
    private static function fromFiles( array $files, ?string $outputPath ): array;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory );
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, [ array $files = [], ?string $outputPath = null, ?string $digest = null, bool $noNonce = false, array $calendar = [], ?int $required = null, ?float $timeout = null, bool $force = false ] ): int;

}
```