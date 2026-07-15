> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method stampMany()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L153)

```php
public function ElephStamp->stampMany( [ CondorcetVote\ElephStamp\FileToStamp ...$files ] ): array
```

## Description
Timestamp several files at once, sharing a single calendar submission.

All files are bound to one merkle tree, so a single commitment covers
them; each file still gets its own independent receipt.

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
