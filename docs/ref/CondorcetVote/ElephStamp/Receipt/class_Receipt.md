> CondorcetVote \ **Receipt**
# Class Receipt
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Receipt.php#L19)

## Description
A timestamp proof for one file — the object form of an `.ots` file.

A receipt is created by {@see \CondorcetVote\ElephStamp\ElephStamp::stamp()} or loaded from existing
bytes. It is refreshed in place by {@see \CondorcetVote\ElephStamp\ElephStamp::upgrade()}, which merges
newly available blockchain attestations into it.
## Elements

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [fromBytes(...)](method_fromBytes.md) | _Load a receipt from the raw bytes of an .ots file._ |
| [fromPath(...)](method_fromPath.md) | _Load a receipt from an .ots file on disk._ |
| [fromSplFileObject(...)](method_fromSplFileObject.md) | _Load a receipt from an already-open, readable .ots file handle._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [bitcoinAttestations(...)](method_bitcoinAttestations.md) | _Every Bitcoin attestation in the proof._ |
| [bitcoinBlockHeight(...)](method_bitcoinBlockHeight.md) | _The lowest Bitcoin block height attesting the timestamp, or null if pending._ |
| [describe(...)](method_describe.md) | _Render the proof as an indented, human-readable string._ |
| [detachedTimestampFile(...)](method_detachedTimestampFile.md) | _The underlying detached timestamp, for advanced use._ |
| [fileDigest(...)](method_fileDigest.md) | _The raw digest of the timestamped file._ |
| [fileDigestHex(...)](method_fileDigestHex.md) | _The digest of the timestamped file as a lower-case hex string._ |
| [hashOperation(...)](method_hashOperation.md) | __ |
| [isComplete(...)](method_isComplete.md) | _Whether the timestamp is confirmed on the Bitcoin blockchain._ |
| [isPending(...)](method_isPending.md) | __ |
| [pendingCalendarUris(...)](method_pendingCalendarUris.md) | _The calendar URIs from which a completed proof can still be fetched._ |
| [saveToPath(...)](method_saveToPath.md) | _Write the receipt to an .ots file on disk._ |
| [status(...)](method_status.md) | __ |
| [toBytes(...)](method_toBytes.md) | _Serialize the receipt to the raw bytes of an .ots file._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Receipt
{

    // Static Methods
    public static function fromBytes( string $bytes ): self;
    public static function fromPath( string $path ): self;
    public static function fromSplFileObject( SplFileObject $file ): self;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\DetachedTimestampFile $detached );
    public function bitcoinAttestations( ): array;
    public function bitcoinBlockHeight( ): ?int;
    public function describe( ): string;
    public function detachedTimestampFile( ): CondorcetVote\ElephStamp\DetachedTimestampFile;
    public function fileDigest( ): string;
    public function fileDigestHex( ): string;
    public function hashOperation( ): CondorcetVote\ElephStamp\Operation\HashOperation;
    public function isComplete( ): bool;
    public function isPending( ): bool;
    public function pendingCalendarUris( ): array;
    public function saveToPath( string $path ): void;
    public function status( ): CondorcetVote\ElephStamp\Status;
    public function toBytes( ): string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Receipt
{

    // Properties
    private readonly CondorcetVote\ElephStamp\DetachedTimestampFile $detached;

    // Static Methods
    public static function fromBytes( string $bytes ): self;
    public static function fromPath( string $path ): self;
    public static function fromSplFileObject( SplFileObject $file ): self;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\DetachedTimestampFile $detached );
    public function bitcoinAttestations( ): array;
    public function bitcoinBlockHeight( ): ?int;
    public function describe( ): string;
    public function detachedTimestampFile( ): CondorcetVote\ElephStamp\DetachedTimestampFile;
    public function fileDigest( ): string;
    public function fileDigestHex( ): string;
    public function hashOperation( ): CondorcetVote\ElephStamp\Operation\HashOperation;
    public function isComplete( ): bool;
    public function isPending( ): bool;
    public function pendingCalendarUris( ): array;
    public function saveToPath( string $path ): void;
    public function status( ): CondorcetVote\ElephStamp\Status;
    public function toBytes( ): string;

}
```