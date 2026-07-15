> CondorcetVote \ [ElephStamp](../../readme.md) \ **Serializer**
# Class Serializer
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Serialization/Serializer.php#L14)

## Description
Writer for the OpenTimestamps binary format.

Accumulates bytes into an in-memory buffer. Timestamp proofs are small
(kilobytes), so buffering the whole output is appropriate here; only the
hashing of the timestamped payload itself is done in a streaming fashion.
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [getBytes(...)](method_getBytes.md) | _Return everything written so far._ |
| [writeBool(...)](method_writeBool.md) | __ |
| [writeBytes(...)](method_writeBytes.md) | _Write fixed-length bytes verbatim (no length prefix)._ |
| [writeUint8(...)](method_writeUint8.md) | __ |
| [writeVarbytes(...)](method_writeVarbytes.md) | _Write variable-length bytes: a varuint length prefix followed by the bytes._ |
| [writeVaruint(...)](method_writeVaruint.md) | _Write a variable-length unsigned integer (unsigned little-endian base-128, LEB128)._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Serialization\Serializer
{

    // Methods
    public function getBytes( ): string;
    public function writeBool( bool $value ): void;
    public function writeBytes( string $value ): void;
    public function writeUint8( int $value ): void;
    public function writeVarbytes( string $value ): void;
    public function writeVaruint( int $value ): void;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Serialization\Serializer
{

    // Properties
    private string $buffer = '';

    // Methods
    public function getBytes( ): string;
    public function writeBool( bool $value ): void;
    public function writeBytes( string $value ): void;
    public function writeUint8( int $value ): void;
    public function writeVarbytes( string $value ): void;
    public function writeVaruint( int $value ): void;

}
```