> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ **UpgradeCommand**
# Class UpgradeCommand
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Command/UpgradeCommand.php#L61)
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| STILL_PENDING | `public const int STILL_PENDING = 2` | _Exit code when everything worked but at least one proof is still pending._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [__invoke(...)](method___invoke.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\UpgradeCommand
{
    // Constants
    public const int STILL_PENDING = 2;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory );
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, array $receipts, [ bool $dryRun = false, bool $all = false, ?string $outputPath = null, array $whitelist = [], bool $noDefaultWhitelist = false, bool $noVerify = false, ?string $node = null, ?string $nodeUser = null, ?string $nodePassword = null, ?string $nodeCookie = null, int $minConfirmations = 6, array $explorer = [], array $explorerUrl = [], ?float $timeout = null, bool $json = false ] ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Command\UpgradeCommand
{
    // Constants
    public const int STILL_PENDING = 2;

    // Properties
    private readonly CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory;

    // Static Methods
    private static function answer( CondorcetVote\ElephStamp\Upgrade\CalendarUpgradeResult $result ): string;
    private static function savedSuffix( bool $saved, bool $dryRun, string $target ): string;
    private static function toArray( string $path, CondorcetVote\ElephStamp\Receipt $receipt, CondorcetVote\ElephStamp\Upgrade\UpgradeReport $report, bool $wasComplete, ?string $savedTo ): array;
    private static function verifiedSuffix( CondorcetVote\ElephStamp\Upgrade\CalendarUpgradeResult $result ): string;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory );
    public function __invoke( Symfony\Component\Console\Style\SymfonyStyle $io, Symfony\Component\Console\Output\OutputInterface $output, array $receipts, [ bool $dryRun = false, bool $all = false, ?string $outputPath = null, array $whitelist = [], bool $noDefaultWhitelist = false, bool $noVerify = false, ?string $node = null, ?string $nodeUser = null, ?string $nodePassword = null, ?string $nodeCookie = null, int $minConfirmations = 6, array $explorer = [], array $explorerUrl = [], ?float $timeout = null, bool $json = false ] ): int;

}
```