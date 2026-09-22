> CondorcetVote \ [ElephStamp](../../readme.md) \ **BlockHeader**
# Class BlockHeader
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/BlockHeader.php#L18)

## Description
The 80-byte header of a Bitcoin block, or the part of it verification needs.

Hashes are kept in internal (little-endian) byte order, as in the header
itself and in proofs; the `*Hex()` accessors give the byte-reversed form
block explorers display.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| RAW_LENGTH | `public const int RAW_LENGTH = 80` | __ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [fromRawHeader(...)](method_fromRawHeader.md) | _Parse a raw 80-byte header, computing its hash and checking that the hash satisfies the difficulty the header itself declares._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [hash(...)](property_hash.md) | __ |
| [height(...)](property_height.md) | __ |
| [merkleRoot(...)](property_merkleRoot.md) | __ |
| [rawHeader(...)](property_rawHeader.md) | __ |
| [time(...)](property_time.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [hashHex(...)](method_hashHex.md) | _The block hash as explorers display it (byte-reversed hex)._ |
| [merkleRootHex(...)](method_merkleRootHex.md) | _The merkle root as explorers display it (byte-reversed hex)._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Verify\BlockHeader
{
    // Constants
    public const int RAW_LENGTH = 80;

    // Properties
    public protected(set) readonly string $hash;
    public protected(set) readonly int $height;
    public protected(set) readonly string $merkleRoot;
    public protected(set) readonly ?string $rawHeader;
    public protected(set) readonly DateTimeImmutable $time;

    // Static Methods
    public static function fromRawHeader( int $height, string $rawHeader ): self;

    // Methods
    public function __construct( int $height, string $hash, string $merkleRoot, DateTimeImmutable $time, [ ?string $rawHeader = null ] );
    public function hashHex( ): string;
    public function merkleRootHex( ): string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Verify\BlockHeader
{
    // Constants
    public const int RAW_LENGTH = 80;

    // Properties
    public protected(set) readonly string $hash;
    public protected(set) readonly int $height;
    public protected(set) readonly string $merkleRoot;
    public protected(set) readonly ?string $rawHeader;
    public protected(set) readonly DateTimeImmutable $time;

    // Static Methods
    public static function fromRawHeader( int $height, string $rawHeader ): self;
    private static function meetsTarget( string $hash, int $bits ): bool;

    // Methods
    public function __construct( int $height, string $hash, string $merkleRoot, DateTimeImmutable $time, [ ?string $rawHeader = null ] );
    public function hashHex( ): string;
    public function merkleRootHex( ): string;

}
```