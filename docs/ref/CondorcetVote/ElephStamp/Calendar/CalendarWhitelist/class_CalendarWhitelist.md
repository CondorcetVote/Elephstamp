> CondorcetVote \ [ElephStamp](../../readme.md) \ **CalendarWhitelist**
# Class CalendarWhitelist
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/CalendarWhitelist.php#L20)

## Description
Allowlist deciding which calendar URIs {@see \CondorcetVote\ElephStamp\ElephStamp::upgrade()}
is permitted to contact.

An `.ots` proof carries the calendar URIs to poll inside its pending
attestations. Since a proof may come from an untrusted source, contacting
those URIs blindly would let an attacker point the process at arbitrary hosts
(an SSRF vector). This whitelist restricts upgrades to hosts you trust.

Patterns are `scheme://host` URLs whose host may contain shell-style globs,
e.g. `https://*.calendar.opentimestamps.org`. Query strings, fragments and
userinfo are never allowed in a matched URL.
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [allows(...)](method_allows.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Calendar\CalendarWhitelist
{

    // Methods
    public function __construct( array $patterns );
    public function allows( string $url ): bool;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Calendar\CalendarWhitelist
{

    // Properties
    private readonly array $patterns;

    // Methods
    public function __construct( array $patterns );
    public function allows( string $url ): bool;

}
```