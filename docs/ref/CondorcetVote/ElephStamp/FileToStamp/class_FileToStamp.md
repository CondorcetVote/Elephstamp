> CondorcetVote \ **FileToStamp**
# Class FileToStamp
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/FileToStamp.php#L23)

## Description
A piece of data to be timestamped, together with its privacy preference.

Built through an explicit named constructor for each supported source. The
digest is always computed in a streaming fashion; file content is never held
in memory in full (except when you deliberately pass it as a string).

By default a random nonce is mixed in so the calendar never learns the real
digest. Call {@see \CondorcetVote\ElephStamp\withoutNonce()} when linkability is acceptable or desired
(the commitment then equals the file's plain hash).
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| CHUNK_SIZE | `private const int CHUNK_SIZE = 1048576` | _Size of the buffer used when reading files, in bytes._ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [fromContent(...)](method_fromContent.md) | _Timestamp a raw string already held in memory._ |
| [fromDigest(...)](method_fromDigest.md) | _Timestamp a digest that has already been computed elsewhere._ |
| [fromPath(...)](method_fromPath.md) | _Timestamp the file at the given path, read as a stream._ |
| [fromSplFileObject(...)](method_fromSplFileObject.md) | _Timestamp an already-open, readable file handle, read as a stream._ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [useNonce(...)](property_useNonce.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [digest(...)](method_digest.md) | _Compute this file's digest with the given hash operation._ |
| [withNonce(...)](method_withNonce.md) | _Return a copy that mixes in a random nonce (the default)._ |
| [withoutNonce(...)](method_withoutNonce.md) | _Return a copy that commits to the plain file digest, without a nonce._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\FileToStamp
{

    // Properties
    public protected(set) readonly bool $useNonce;

    // Static Methods
    public static function fromContent( string $content ): self;
    public static function fromDigest( string $digest ): self;
    public static function fromPath( string $path ): self;
    public static function fromSplFileObject( SplFileObject $file ): self;

    // Methods
    public function digest( CondorcetVote\ElephStamp\Operation\HashOperation $operation ): string;
    public function withNonce( ): self;
    public function withoutNonce( ): self;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\FileToStamp
{
    // Constants
    private const int CHUNK_SIZE = 1048576;

    // Properties
    public protected(set) readonly bool $useNonce;
    private readonly Closure $digestFactory;

    // Static Methods
    public static function fromContent( string $content ): self;
    public static function fromDigest( string $digest ): self;
    public static function fromPath( string $path ): self;
    public static function fromSplFileObject( SplFileObject $file ): self;
    private static function readChunks( SplFileObject $file ): iterable;

    // Methods
    public function digest( CondorcetVote\ElephStamp\Operation\HashOperation $operation ): string;
    public function withNonce( ): self;
    public function withoutNonce( ): self;

}
```