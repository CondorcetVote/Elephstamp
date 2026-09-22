> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **InfoCommand**
# Class InfoCommand
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Command/InfoCommand.php#L45)
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__invoke(...)](method___invoke.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\InfoCommand
{

    // Methods
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, array $receipts, [ ?string $file = null, bool $json = false, array $whitelist = [], bool $noDefaultWhitelist = false ] ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\InfoCommand
{

    // Static Methods
    private static function anchorLines( CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor $anchor ): array;
    private static function digestMatches( CondorcetVote\ElephStamp\Console\Inspection\ProofInspection $inspection, string $original ): ?bool;
    private static function originalLine( ?string $original, ?string $expected, ?bool $matches ): string;
    private static function siblingCandidate( string $receiptPath ): ?string;
    private static function siblingFile( string $receiptPath ): ?string;
    private static function statusLine( CondorcetVote\ElephStamp\Console\Inspection\ProofInspection $inspection ): string;
    private static function submissionState( CondorcetVote\ElephStamp\Console\Inspection\CalendarSubmission $submission, CondorcetVote\ElephStamp\Status $status ): string;
    private static function toArray( string $path, CondorcetVote\ElephStamp\Console\Inspection\ProofInspection $inspection, ?string $original, ?bool $matches ): array;

    // Methods
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, array $receipts, [ ?string $file = null, bool $json = false, array $whitelist = [], bool $noDefaultWhitelist = false ] ): int;

}
```