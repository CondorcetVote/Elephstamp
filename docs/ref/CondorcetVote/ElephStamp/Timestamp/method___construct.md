> CondorcetVote \ [Timestamp](class_Timestamp.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L38)

```php
public function Timestamp->__construct( ?string $msg )
```

## Parameters

### **msg:**
```php
?string $msg
```
**Type:** `?string`

the message this node commits to; null when it is
unknown because the node sits below a
non-computable operation (keccak256)
