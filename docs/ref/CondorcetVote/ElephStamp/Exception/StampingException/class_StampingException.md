> CondorcetVote \ [ElephStamp](../../readme.md) \ **StampingException**
# Class StampingException
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Exception/StampingException.php#L13)

## Description
Thrown when a stamp request cannot gather enough calendar attestations to
satisfy the required threshold (the m-of-n policy).
## Elements


## Public Representation
```php
final class CondorcetVote\ElephStamp\Exception\StampingException extends RuntimeException implements Throwable, Stringable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Exception\StampingException extends RuntimeException implements Throwable, Stringable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

    // Inherited Properties
    protected  StampingException->code = 0;
    protected string StampingException->file = '';
    protected int StampingException->line = 0;
    protected  StampingException->message = '';

}
```