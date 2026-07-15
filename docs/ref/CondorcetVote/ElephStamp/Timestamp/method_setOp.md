> CondorcetVote \ [Timestamp](class_Timestamp.md)
# Method setOp()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L73)

```php
public function Timestamp->setOp( CondorcetVote\ElephStamp\Operation\Operation $operation, self $child ): void
```

## Description
Bind a specific child timestamp to an operation edge.

## Parameters

### **operation:**
```php
CondorcetVote\ElephStamp\Operation\Operation $operation
```
**Type:** [`CondorcetVote\ElephStamp\Operation\Operation`](../Operation/Operation/class_Operation.md)



### **child:**
```php
self $child
```
**Type:** [`CondorcetVote\ElephStamp\Timestamp`](class_Timestamp.md)



## Return
**Type:** `void`



## Throws
- **[\CondorcetVote\ElephStamp\Exception\SerializationException](../Exception/SerializationException/class_SerializationException.md)** _if the child is for a different message_
