> CondorcetVote \ [ElephStamp](../../readme.md) \ [HashOperation](../HashOperation/class_HashOperation.md)
# Method comparisonKey()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/Operation.php#L84)

```php
public function Operation->comparisonKey( ): string
```

## Description
Key used to order operations deterministically within a timestamp.

Ordering is by tag byte first, then by argument bytes, which matches the
canonical ordering of the reference implementation.

## Return
**Type:** `string`


