> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **TreeCommand**
# Class TreeCommand
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Command/TreeCommand.php#L33)
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__invoke(...)](method___invoke.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\TreeCommand
{

    // Methods
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, array $receipts, [ bool $plain = false, bool $noHashes = false ] ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\TreeCommand
{

    // Static Methods
    private static function attestationLabel( CondorcetVote\ElephStamp\Attestation\TimeAttestation $attestation ): string;
    private static function children( CondorcetVote\ElephStamp\Timestamp $node, bool $withHashes ): array;
    private static function operationLabel( CondorcetVote\ElephStamp\Operation\Operation $op, CondorcetVote\ElephStamp\Timestamp $result, bool $withHashes ): string;

    // Methods
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, array $receipts, [ bool $plain = false, bool $noHashes = false ] ): int;

}
```