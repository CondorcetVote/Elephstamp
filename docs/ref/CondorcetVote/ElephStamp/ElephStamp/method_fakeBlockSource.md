> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method fakeBlockSource()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L166)

```php
public function ElephStamp->fakeBlockSource( ): CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource
```

## Description
The fake block source backing this client, to register the blocks a
receipt should verify against.

## Return
**Type:** [`CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource`](../Verify/FakeBlockHeaderSource/class_FakeBlockHeaderSource.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if this client is not in fake mode_
