> CondorcetVote \ [ElephStamp](../../readme.md) \ **CalendarWhitelist**
# Class CalendarWhitelist
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/CalendarWhitelist.php#L34)

## Description
Allowlist deciding which calendar URIs {@see \CondorcetVote\ElephStamp\ElephStamp::upgrade()}
is permitted to contact.

An `.ots` proof carries the calendar URIs to poll inside its pending
attestations. Since a proof may come from an untrusted source, contacting
those URIs blindly would let an attacker point the process at arbitrary hosts
(an SSRF vector). This whitelist restricts upgrades to hosts you trust.

Patterns are `https://host` URLs whose host may contain `*` globs,
e.g. `https://*.calendar.opentimestamps.org`. A glob must be followed by at
least two literal domain labels, the last one not numeric: `https://*`,
`https://*.example` or `https://10.0.0.*` would reach any host, a whole
top-level domain or a private network. A pattern without a glob is taken
as an explicit choice and may name any host, IP addresses included. Only
https is accepted: a plaintext calendar connection would let a network
attacker inject forged proofs. Query strings, fragments and userinfo are
never allowed in a matched URL.

URLs are parsed strictly as RFC 3986 and compared in their normalized form
(lowercase host, decoded unreserved characters, no dot segments, no default
port). {@see \CondorcetVote\ElephStamp\Calendar\resolve()} returns that normalized form, so the URL checked is
exactly the URL contacted.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| HTTPS_PORT | `private const int HTTPS_PORT = 443` | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [allows(...)](method_allows.md) | __ |
| [resolve(...)](method_resolve.md) | _The normalized URL to contact for $url, or null when it is not allowed._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Calendar\CalendarWhitelist
{

    // Methods
    public function __construct( array $patterns );
    public function allows( string $url ): bool;
    public function resolve( string $url ): ?string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Calendar\CalendarWhitelist
{
    // Constants
    private const int HTTPS_PORT = 443;

    // Properties
    private readonly array $rules;

    // Static Methods
    private static function hasGlob( string $host ): bool;
    private static function hostMatches( string $pattern, string $host ): bool;
    private static function isAnchored( string $host ): bool;
    private static function parse( string $url ): ?array;

    // Methods
    public function __construct( array $patterns );
    public function allows( string $url ): bool;
    public function resolve( string $url ): ?string;

}
```