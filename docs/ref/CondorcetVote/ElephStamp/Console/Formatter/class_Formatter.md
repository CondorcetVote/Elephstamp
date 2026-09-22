> CondorcetVote \ [ElephStamp](../../readme.md) \ **Formatter**
# Class Formatter
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Formatter.php#L12)

## Description
Small presentation helpers shared by the commands.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| ABBREVIATED_EDGE | `private const int ABBREVIATED_EDGE = 8` | __ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [abbreviate(...)](method_abbreviate.md) | _Shorten a hex string to first8…last8 when it is longer than that._ |
| [bytes(...)](method_bytes.md) | _Human-friendly byte count._ |
| [hex(...)](method_hex.md) | _Hex-encode raw bytes, abbreviated to first8…last8 unless $full is set._ |
| [plural(...)](method_plural.md) | _"1 file" / "3 files"._ |
| [status(...)](method_status.md) | _A coloured one-word status, e.g. "<fg=green>complete</>"._ |
| [statusName(...)](method_statusName.md) | _Lower-case machine name of a status, for JSON output._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\Formatter
{

    // Static Methods
    public static function abbreviate( string $hex ): string;
    public static function bytes( int $bytes ): string;
    public static function hex( string $raw, [ bool $full = false ] ): string;
    public static function plural( int $count, string $singular, [ ?string $plural = null ] ): string;
    public static function status( CondorcetVote\ElephStamp\Status $status ): string;
    public static function statusName( CondorcetVote\ElephStamp\Status $status ): string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\Formatter
{
    // Constants
    private const int ABBREVIATED_EDGE = 8;

    // Static Methods
    public static function abbreviate( string $hex ): string;
    public static function bytes( int $bytes ): string;
    public static function hex( string $raw, [ bool $full = false ] ): string;
    public static function plural( int $count, string $singular, [ ?string $plural = null ] ): string;
    public static function status( CondorcetVote\ElephStamp\Status $status ): string;
    public static function statusName( CondorcetVote\ElephStamp\Status $status ): string;

}
```