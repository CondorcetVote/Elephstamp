> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method fakeCalendar()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L143)

```php
public function ElephStamp->fakeCalendar( ): CondorcetVote\ElephStamp\Calendar\FakeCalendarClient
```

## Description
The fake calendar backing this client, for driving its lifecycle in tests.

## Return
**Type:** [`CondorcetVote\ElephStamp\Calendar\FakeCalendarClient`](../Calendar/FakeCalendarClient/class_FakeCalendarClient.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if this client is not in fake mode_
