> CondorcetVote \ [ElephStamp](../../readme.md) \ [Append](../Append/class_Append.md)
# Method isComputable()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/Operation.php#L74)

```php
public function Operation->isComputable( ): bool
```

## Description
Whether this library can compute the operation's result.

Every operation the format currently defines is computable. A
non-computable one would still parse and serialize, but the subtree
below it would carry unknown messages: it could be neither verified,
upgraded, nor extended.

## Return
**Type:** `bool`


