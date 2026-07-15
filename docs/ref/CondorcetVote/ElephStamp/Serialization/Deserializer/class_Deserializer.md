> CondorcetVote \ [ElephStamp](../../readme.md) \ **Deserializer**
# Class Deserializer
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Serialization/Deserializer.php#L15)

## Description
Reader for the OpenTimestamps binary format.

Consumes a fixed byte string with a moving cursor. All read failures raise
{@see \CondorcetVote\ElephStamp\Exception\SerializationException}.
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [assertEof(...)](method_assertEof.md) | _Assert that the cursor has reached the end of the data._ |
| [assertMagic(...)](method_assertMagic.md) | _Assert that the upcoming bytes match the expected magic header._ |
| [isEof(...)](method_isEof.md) | __ |
| [readBool(...)](method_readBool.md) | __ |
| [readBytes(...)](method_readBytes.md) | _Read exactly $count raw bytes, advancing the cursor._ |
| [readUint8(...)](method_readUint8.md) | __ |
| [readVarbytes(...)](method_readVarbytes.md) | _Read variable-length bytes: a varuint length prefix, then that many bytes._ |
| [readVaruint(...)](method_readVaruint.md) | _Read a variable-length unsigned integer (LEB128)._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Serialization\Deserializer
{

    // Methods
    public function __construct( string $data );
    public function assertEof( ): void;
    public function assertMagic( string $expectedMagic ): void;
    public function isEof( ): bool;
    public function readBool( ): bool;
    public function readBytes( int $count ): string;
    public function readUint8( ): int;
    public function readVarbytes( int $maxLength, [ int $minLength = 0 ] ): string;
    public function readVaruint( ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Serialization\Deserializer
{

    // Properties
    private readonly string $data;
    private readonly int $length;
    private int $offset = 0;

    // Methods
    public function __construct( string $data );
    public function assertEof( ): void;
    public function assertMagic( string $expectedMagic ): void;
    public function isEof( ): bool;
    public function readBool( ): bool;
    public function readBytes( int $count ): string;
    public function readUint8( ): int;
    public function readVarbytes( int $maxLength, [ int $minLength = 0 ] ): string;
    public function readVaruint( ): int;

}
```