> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method stampMany()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L216)

```php
public function ElephStamp->stampMany( [ CondorcetVote\ElephStamp\FileToStamp ...$files ] ): array
```

## Description
Timestamp several files at once, sharing a single calendar submission.

All files are bound to one merkle tree, so a single commitment covers
them; each file still gets its own independent receipt. The receipts of
a batch share their tree nodes in memory: upgrading one also refreshes
its siblings, until they are reloaded from disk.

Privacy: the merkle tree embeds each leaf's message into the proofs of
its neighbours. A file stamped {@see \CondorcetVote\ElephStamp\FileToStamp::withoutNonce()} in a
batch therefore exposes its plain digest to whoever holds a sibling
receipt, in addition to the calendars.

## Parameters

### **files:**
```php
CondorcetVote\ElephStamp\FileToStamp ...$files
```
**Type:** [`CondorcetVote\ElephStamp\FileToStamp`](../FileToStamp/class_FileToStamp.md)



## Return
**Type:** `array`



## Throws
- **[\CondorcetVote\ElephStamp\Exception\StampingException](../Exception/StampingException/class_StampingException.md)** _if too few calendars accept the request_
