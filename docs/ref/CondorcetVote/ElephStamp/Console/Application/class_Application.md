> CondorcetVote \ [ElephStamp](../../readme.md) \ **Application**
# Class Application
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Application.php#L18)

## Description
The `elephstamp` command-line tool.

Pass a custom {@see \CondorcetVote\ElephStamp\Console\ClientFactory} to run the commands against a fake
client, as the CLI test suite does.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| PACKAGE | `public const string PACKAGE = 'condorcet-vote/elephstamp'` | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Application extends Symfony\Component\Console\Application implements Symfony\Contracts\Service\ResetInterface
{
    // Constants
    public const string PACKAGE = 'condorcet-vote/elephstamp';

    // Methods
    public function __construct( [ ?CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory = null ] );

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Application extends Symfony\Component\Console\Application implements Symfony\Contracts\Service\ResetInterface
{
    // Constants
    public const string PACKAGE = 'condorcet-vote/elephstamp';

    // Static Methods
    public static function getAbbreviations( array $names ): array;
    private static function version( ): string;

    // Methods
    public function __construct( [ ?CondorcetVote\ElephStamp\Console\ClientFactory $clientFactory = null ] );

}
```