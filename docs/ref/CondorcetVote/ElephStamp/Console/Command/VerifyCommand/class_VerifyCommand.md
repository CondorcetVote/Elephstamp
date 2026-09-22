> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **VerifyCommand**
# Class VerifyCommand
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Command/VerifyCommand.php#L49)
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| NOT_YET | `public const int NOT_YET = 2` | _Exit code when nothing is wrong but the proof cannot be verified yet._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [__invoke(...)](method___invoke.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\VerifyCommand
{
    // Constants
    public const int NOT_YET = 2;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory );
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, array $receipts, [ ?string $file = null, ?string $digest = null, array $explorer = [], array $explorerUrl = [], int $minConfirmations = 6, ?float $timeout = null, bool $json = false ] ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\VerifyCommand
{
    // Constants
    public const int NOT_YET = 2;

    // Properties
    private readonly CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory;

    // Static Methods
    private static function exitCode( CondorcetVote\ElephStamp\Verify\Verdict $verdict ): int;
    private static function mismatchWarning( CondorcetVote\ElephStamp\Verify\VerificationReport $report ): ?string;
    private static function originalLine( ?string $original, ?bool $matches ): string;
    private static function result( CondorcetVote\ElephStamp\Verify\AnchorVerification $verification, int $required ): string;
    private static function subject( string $receiptPath, ?string $file, ?string $digest ): array;
    private static function toArray( string $path, CondorcetVote\ElephStamp\Receipt $receipt, CondorcetVote\ElephStamp\Verify\VerificationReport $report, ?string $original ): array;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory );
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, array $receipts, [ ?string $file = null, ?string $digest = null, array $explorer = [], array $explorerUrl = [], int $minConfirmations = 6, ?float $timeout = null, bool $json = false ] ): int;

}
```