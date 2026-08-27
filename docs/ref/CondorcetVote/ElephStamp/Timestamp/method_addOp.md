> CondorcetVote \ [Timestamp](class_Timestamp.md)
# Method addOp()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L69)

```php
public function Timestamp->addOp( CondorcetVote\ElephStamp\Operation\Operation $operation ): self
```

## Description
Add an operation edge, returning the timestamp of its result.

If the operation is already present its existing child timestamp is
returned, so the tree stays a proper DAG.

## Parameters

### **operation:**
```php
CondorcetVote\ElephStamp\Operation\Operation $operation
```
**Type:** [`CondorcetVote\ElephStamp\Operation\Operation`](../Operation/Operation/class_Operation.md)



## Return
**Type:** [`CondorcetVote\ElephStamp\Timestamp`](class_Timestamp.md)


