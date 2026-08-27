> CondorcetVote \ [Timestamp](class_Timestamp.md)
# Method merge()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L120)

```php
public function Timestamp->merge( self $other ): bool
```

## Description
Merge every operation and attestation from another timestamp into this one.

## Parameters

### **other:**
```php
self $other
```
**Type:** [`CondorcetVote\ElephStamp\Timestamp`](class_Timestamp.md)



## Return
**Type:** `bool`

whether the merge added anything new to the tree

## Throws
- **[\CondorcetVote\ElephStamp\Exception\SerializationException](../Exception/SerializationException/class_SerializationException.md)** _if the timestamps are for different messages_
