> CondorcetVote \ **DetachedTimestampFile**
# Class DetachedTimestampFile
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/DetachedTimestampFile.php#L15)

## Description
A timestamp bound to the digest of a specific file â€” the content of an
`.ots` proof file.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| HEADER_MAGIC | `public const string HEADER_MAGIC = '' . "\0" . 'OpenTimestamps' . "\0" . '' . "\0" . 'Proof' . "\0" . '¿‰âè„è’”'` | _Header magic. Designed to give a hint in a hexdump while being detected as "data" by the file utility._ |
| MAJOR_VERSION | `public const int MAJOR_VERSION = 1` | __ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [fromBytes(...)](method_fromBytes.md) | _Parse the raw bytes of an .ots file._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [fileHashOperation(...)](property_fileHashOperation.md) | __ |
| [timestamp(...)](property_timestamp.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [fileDigest(...)](method_fileDigest.md) | _The digest of the timestamped file._ |
| [serialize(...)](method_serialize.md) | __ |
| [toBytes(...)](method_toBytes.md) | _Serialize to the raw bytes of an .ots file._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\DetachedTimestampFile
{
    // Constants
    public const string HEADER_MAGIC = '' . "\0" . 'OpenTimestamps' . "\0" . '' . "\0" . 'Proof' . "\0" . '¿‰âè„è’”';
    public const int MAJOR_VERSION = 1;

    // Properties
    public protected(set) readonly CondorcetVote\ElephStamp\Operation\HashOperation $fileHashOperation;
    public protected(set) readonly CondorcetVote\ElephStamp\Timestamp $timestamp;

    // Static Methods
    public static function fromBytes( string $bytes ): self;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Operation\HashOperation $fileHashOperation, CondorcetVote\ElephStamp\Timestamp $timestamp );
    public function fileDigest( ): string;
    public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    public function toBytes( ): string;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\DetachedTimestampFile
{
    // Constants
    public const string HEADER_MAGIC = '' . "\0" . 'OpenTimestamps' . "\0" . '' . "\0" . 'Proof' . "\0" . '¿‰âè„è’”';
    public const int MAJOR_VERSION = 1;

    // Properties
    public protected(set) readonly CondorcetVote\ElephStamp\Operation\HashOperation $fileHashOperation;
    public protected(set) readonly CondorcetVote\ElephStamp\Timestamp $timestamp;

    // Static Methods
    public static function fromBytes( string $bytes ): self;

    // Methods
    public function __construct( CondorcetVote\ElephStamp\Operation\HashOperation $fileHashOperation, CondorcetVote\ElephStamp\Timestamp $timestamp );
    public function fileDigest( ): string;
    public function serialize( CondorcetVote\ElephStamp\Serialization\Serializer $serializer ): void;
    public function toBytes( ): string;

}
```