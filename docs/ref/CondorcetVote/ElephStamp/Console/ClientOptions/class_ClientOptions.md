> CondorcetVote \ [ElephStamp](../../readme.md) \ **ClientOptions**
# Class ClientOptions
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/ClientOptions.php#L17)

## Description
Calendar-related settings collected from the command line.

Every field is optional: an empty or null value means "use the library
default", so the same object serves both stamping and upgrading commands.
## Elements

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [explorersFromNames(...)](method_explorersFromNames.md) | _Resolve the explorer names given on the command line._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [calendarUrls(...)](property_calendarUrls.md) | __ |
| [explorers(...)](property_explorers.md) | __ |
| [explorerUrls(...)](property_explorerUrls.md) | __ |
| [requiredCalendars(...)](property_requiredCalendars.md) | __ |
| [timeout(...)](property_timeout.md) | __ |
| [useDefaultWhitelist(...)](property_useDefaultWhitelist.md) | __ |
| [whitelist(...)](property_whitelist.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [resolvedCalendarUrls(...)](method_resolvedCalendarUrls.md) | _The calendars a stamp is submitted to._ |
| [resolvedExplorers(...)](method_resolvedExplorers.md) | _The explorers verification consults: the chosen ones, or the default when none was chosen and no custom URL was given._ |
| [resolvedWhitelist(...)](method_resolvedWhitelist.md) | _The host patterns an upgrade may contact._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\ClientOptions
{

    // Properties
    public protected(set) readonly array $calendarUrls;
    public protected(set) readonly array $explorers;
    public protected(set) readonly array $explorerUrls;
    public protected(set) readonly ?int $requiredCalendars;
    public protected(set) readonly ?float $timeout;
    public protected(set) readonly bool $useDefaultWhitelist;
    public protected(set) readonly array $whitelist;

    // Static Methods
    public static function explorersFromNames( array $names ): array;

    // Methods
    public function __construct( [ array $calendarUrls = [], ?int $requiredCalendars = null, array $whitelist = [], bool $useDefaultWhitelist = true, ?float $timeout = null, array $explorers = [], array $explorerUrls = [] ] );
    public function resolvedCalendarUrls( ): array;
    public function resolvedExplorers( ): array;
    public function resolvedWhitelist( ): array;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\ClientOptions
{

    // Properties
    public protected(set) readonly array $calendarUrls;
    public protected(set) readonly array $explorers;
    public protected(set) readonly array $explorerUrls;
    public protected(set) readonly ?int $requiredCalendars;
    public protected(set) readonly ?float $timeout;
    public protected(set) readonly bool $useDefaultWhitelist;
    public protected(set) readonly array $whitelist;

    // Static Methods
    public static function explorersFromNames( array $names ): array;

    // Methods
    public function __construct( [ array $calendarUrls = [], ?int $requiredCalendars = null, array $whitelist = [], bool $useDefaultWhitelist = true, ?float $timeout = null, array $explorers = [], array $explorerUrls = [] ] );
    public function resolvedCalendarUrls( ): array;
    public function resolvedExplorers( ): array;
    public function resolvedWhitelist( ): array;

}
```