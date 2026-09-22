> CondorcetVote \ [ElephStamp](../../readme.md) \ [FakeBlockHeaderSource](class_FakeBlockHeaderSource.md)
# Method anchor()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/FakeBlockHeaderSource.php#L55)

```php
public function FakeBlockHeaderSource->anchor( CondorcetVote\ElephStamp\Receipt $receipt, [ ?DateTimeImmutable $time = null ] ): void
```

## Description
Register a block for every Bitcoin attestation of the receipt, so that
verifying it succeeds.

## Parameters

### **receipt:**
```php
CondorcetVote\ElephStamp\Receipt $receipt
```
**Type:** [`CondorcetVote\ElephStamp\Receipt`](../../Receipt/class_Receipt.md)



### **time:**
```php
?DateTimeImmutable $time = null
```
**Type:** `?DateTimeImmutable`



## Return
**Type:** `void`


