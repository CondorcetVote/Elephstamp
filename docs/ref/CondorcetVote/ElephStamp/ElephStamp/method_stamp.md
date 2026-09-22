> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method stamp()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L194)

```php
public function ElephStamp->stamp( CondorcetVote\ElephStamp\FileToStamp $file ): CondorcetVote\ElephStamp\Receipt
```

## Description
Timestamp a single file.

## Parameters

### **file:**
```php
CondorcetVote\ElephStamp\FileToStamp $file
```
**Type:** [`CondorcetVote\ElephStamp\FileToStamp`](../FileToStamp/class_FileToStamp.md)



## Return
**Type:** [`CondorcetVote\ElephStamp\Receipt`](../Receipt/class_Receipt.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\StampingException](../Exception/StampingException/class_StampingException.md)** _if too few calendars accept the request_
