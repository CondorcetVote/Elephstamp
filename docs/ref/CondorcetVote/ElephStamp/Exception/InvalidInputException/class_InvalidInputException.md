> CondorcetVote \ [ElephStamp](../../readme.md) \ **InvalidInputException**
# Class InvalidInputException
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Exception/InvalidInputException.php#L13)

## Description
Thrown when caller-supplied input is invalid: an unreadable file, an empty
digest, an out-of-range value, and so on.
## Elements


## Public Representation
```php
final class CondorcetVote\ElephStamp\Exception\InvalidInputException extends InvalidArgumentException implements Stringable, Throwable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Exception\InvalidInputException extends InvalidArgumentException implements Stringable, Throwable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

    // Inherited Properties
    protected  InvalidInputException->code = 0;
    protected string InvalidInputException->file = '';
    protected int InvalidInputException->line = 0;
    protected  InvalidInputException->message = '';

}
```