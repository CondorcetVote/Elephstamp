> CondorcetVote \ [FileToStamp](class_FileToStamp.md)
# Method withoutNonce()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/FileToStamp.php#L130)

```php
public function FileToStamp->withoutNonce( ): self
```

## Description
Return a copy that commits to the plain file digest, without a nonce.

Privacy caveats: the calendars learn the plain digest, and inside a
{@see \CondorcetVote\ElephStamp\ElephStamp::stampMany()} batch the digest is also embedded in the
sibling receipts, so whoever holds one of them learns it too.

## Return
**Type:** [`CondorcetVote\ElephStamp\FileToStamp`](class_FileToStamp.md)


