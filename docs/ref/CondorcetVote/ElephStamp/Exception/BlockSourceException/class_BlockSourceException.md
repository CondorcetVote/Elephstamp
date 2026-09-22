> CondorcetVote \ [ElephStamp](../../readme.md) \ **BlockSourceException**
# Class BlockSourceException
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Exception/BlockSourceException.php#L13)

## Description
Thrown when a block header source (block explorer, node) cannot deliver a
header: unreachable, unknown block, malformed or inconsistent answer.
## Elements


## Public Representation
```php
final class CondorcetVote\ElephStamp\Exception\BlockSourceException extends RuntimeException implements Throwable, Stringable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Exception\BlockSourceException extends RuntimeException implements Throwable, Stringable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

    // Inherited Properties
    protected  BlockSourceException->code = 0;
    protected string BlockSourceException->file = '';
    protected int BlockSourceException->line = 0;
    protected  BlockSourceException->message = '';

}
```