> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method fake()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L128)

```php
public static function ElephStamp::fake( [ ?CondorcetVote\ElephStamp\Calendar\FakeCalendarClient $calendar = null ] ): self
```

## Description
Build a fully offline, deterministic client for tests and local environments.

Pass the same {@see \CondorcetVote\ElephStamp\Calendar\FakeCalendarClient} to several calls to share its
confirmation state, or read it back with {@see \CondorcetVote\ElephStamp\fakeCalendar()}.

## Parameters

### **calendar:**
```php
?CondorcetVote\ElephStamp\Calendar\FakeCalendarClient $calendar = null
```
**Type:** [`?CondorcetVote\ElephStamp\Calendar\FakeCalendarClient`](../Calendar/FakeCalendarClient/class_FakeCalendarClient.md)



## Return
**Type:** [`CondorcetVote\ElephStamp\ElephStamp`](class_ElephStamp.md)


