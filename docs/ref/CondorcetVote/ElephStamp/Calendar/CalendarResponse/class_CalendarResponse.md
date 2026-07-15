> CondorcetVote \ [ElephStamp](../../readme.md) \ **CalendarResponse**
# Class CalendarResponse
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/CalendarResponse.php#L18)

## Description
The outcome of contacting a single calendar.

Exactly one of three states holds: success (a timestamp was returned),
not-found (the calendar has no attestation yet — a normal pending result of
an upgrade), or failure (the calendar was unreachable or misbehaved). A
failure never aborts a batch: the caller decides what to do with it.
## Elements

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [failure(...)](method_failure.md) | __ |
| [notFound(...)](method_notFound.md) | __ |
| [success(...)](method_success.md) | __ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [calendarUrl(...)](property_calendarUrl.md) | __ |
| [error(...)](property_error.md) | __ |
| [notFound(...)](property_notFound.md) | __ |
| [timestamp(...)](property_timestamp.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [isFailure(...)](method_isFailure.md) | __ |
| [isNotFound(...)](method_isNotFound.md) | __ |
| [isSuccess(...)](method_isSuccess.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Calendar\CalendarResponse
{

    // Properties
    public protected(set) readonly string $calendarUrl;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Exception\ElephStampException $error;
    public protected(set) readonly bool $notFound;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Timestamp $timestamp;

    // Static Methods
    public static function failure( string $calendarUrl, CondorcetVote\ElephStamp\Exception\ElephStampException $error ): self;
    public static function notFound( string $calendarUrl ): self;
    public static function success( string $calendarUrl, CondorcetVote\ElephStamp\Timestamp $timestamp ): self;

    // Methods
    public function isFailure( ): bool;
    public function isNotFound( ): bool;
    public function isSuccess( ): bool;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Calendar\CalendarResponse
{

    // Properties
    public protected(set) readonly string $calendarUrl;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Exception\ElephStampException $error;
    public protected(set) readonly bool $notFound;
    public protected(set) readonly ?CondorcetVote\ElephStamp\Timestamp $timestamp;

    // Static Methods
    public static function failure( string $calendarUrl, CondorcetVote\ElephStamp\Exception\ElephStampException $error ): self;
    public static function notFound( string $calendarUrl ): self;
    public static function success( string $calendarUrl, CondorcetVote\ElephStamp\Timestamp $timestamp ): self;

    // Methods
    public function isFailure( ): bool;
    public function isNotFound( ): bool;
    public function isSuccess( ): bool;

}
```