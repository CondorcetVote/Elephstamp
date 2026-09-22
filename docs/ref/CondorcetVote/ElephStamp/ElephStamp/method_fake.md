> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method fake()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L141)

```php
public static function ElephStamp::fake( [ ?CondorcetVote\ElephStamp\Calendar\FakeCalendarClient $calendar = null, ?CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource $blockHeaderSource = null ] ): self
```

## Description
Build a fully offline, deterministic client for tests and local environments.

Pass the same {@see \CondorcetVote\ElephStamp\Calendar\FakeCalendarClient} to several calls to share its
confirmation state, or read it back with {@see \CondorcetVote\ElephStamp\fakeCalendar()}. The
fake calendar mines every confirmation into its
{@see \CondorcetVote\ElephStamp\Calendar\FakeCalendarClient::blocks()}, which is the block source this
client verifies against, so a confirmed receipt upgrades and verifies
without further setup.

## Parameters

### **calendar:**
```php
?CondorcetVote\ElephStamp\Calendar\FakeCalendarClient $calendar = null
```
**Type:** [`?CondorcetVote\ElephStamp\Calendar\FakeCalendarClient`](../Calendar/FakeCalendarClient/class_FakeCalendarClient.md)



### **blockHeaderSource:**
```php
?CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource $blockHeaderSource = null
```
**Type:** [`?CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource`](../Verify/FakeBlockHeaderSource/class_FakeBlockHeaderSource.md)



## Return
**Type:** [`CondorcetVote\ElephStamp\ElephStamp`](class_ElephStamp.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if both a calendar and a block source are given and the calendar does not mine into that source_
