> CondorcetVote \ [ElephStamp](../../readme.md) \ **BitcoinTransaction**
# Class BitcoinTransaction
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Bitcoin/BitcoinTransaction.php#L15)

## Description
The Bitcoin transaction carrying a commitment, as recovered from a proof.

A proof does not name the transaction: it embeds its raw bytes around the
commitment and hashes them on the way to the block's merkle root. This
class is the result of recognising that step, giving a transaction id that
can be looked up in any block explorer.
## Elements

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [tryFromBytes(...)](method_tryFromBytes.md) | _Build from raw transaction bytes, computing the id._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [rawBytes(...)](property_rawBytes.md) | __ |
| [txid(...)](property_txid.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [size(...)](method_size.md) | _Size of the witness-stripped transaction, in bytes._ |
| [txidHex(...)](method_txidHex.md) | _The transaction id as block explorers display it (byte-reversed hex)._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction
{

    // Properties
    public protected(set) readonly string $rawBytes;
    public protected(set) readonly string $txid;

    // Static Methods
    public static function tryFromBytes( string $rawBytes ): ?self;

    // Methods
    public function __construct( string $rawBytes, string $txid );
    public function size( ): int;
    public function txidHex( ): string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction
{

    // Properties
    public protected(set) readonly string $rawBytes;
    public protected(set) readonly string $txid;

    // Static Methods
    public static function tryFromBytes( string $rawBytes ): ?self;

    // Methods
    public function __construct( string $rawBytes, string $txid );
    public function size( ): int;
    public function txidHex( ): string;

}
```