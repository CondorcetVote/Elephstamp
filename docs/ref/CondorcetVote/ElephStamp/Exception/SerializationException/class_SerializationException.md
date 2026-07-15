> CondorcetVote \ [ElephStamp](../../readme.md) \ **SerializationException**
# Class SerializationException
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Exception/SerializationException.php#L15)

## Description
Thrown when reading or writing the OpenTimestamps binary format fails.

Covers bad magic bytes, truncated data, trailing garbage, unsupported
versions, unknown operation tags and recursion-limit violations.
## Elements


## Public Representation
```php
final class CondorcetVote\ElephStamp\Exception\SerializationException extends RuntimeException implements Throwable, Stringable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Exception\SerializationException extends RuntimeException implements Throwable, Stringable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

    // Inherited Properties
    protected  SerializationException->code = 0;
    protected string SerializationException->file = '';
    protected int SerializationException->line = 0;
    protected  SerializationException->message = '';

}
```