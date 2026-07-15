> CondorcetVote \ [ElephStamp](../../readme.md) \ **CalendarException**
# Class CalendarException
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Exception/CalendarException.php#L13)

## Description
Thrown when a calendar server cannot be reached or returns an unexpected
response (network error, non-200/404 status, oversized body, ...).
## Elements


## Public Representation
```php
class CondorcetVote\ElephStamp\Exception\CalendarException extends RuntimeException implements Throwable, Stringable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

}
```

## Full Representation
```php
class CondorcetVote\ElephStamp\Exception\CalendarException extends RuntimeException implements Throwable, Stringable, CondorcetVote\ElephStamp\Exception\ElephStampException
{

    // Inherited Properties
    protected  CalendarException->code = 0;
    protected string CalendarException->file = '';
    protected int CalendarException->line = 0;
    protected  CalendarException->message = '';

}
```