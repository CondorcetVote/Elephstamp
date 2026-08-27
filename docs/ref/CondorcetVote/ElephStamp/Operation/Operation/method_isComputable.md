> CondorcetVote \ [ElephStamp](../../readme.md) \ [Append](../Append/class_Append.md)
# Method isComputable()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/Operation.php#L73)

```php
public function Operation->isComputable( ): bool
```

## Description
Whether this library can compute the operation's result.

A non-computable operation (keccak256) still parses and serializes, but
the subtree below it carries unknown messages: it cannot be verified,
upgraded, or extended.

## Return
**Type:** `bool`


