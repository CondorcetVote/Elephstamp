> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method upgrade()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L184)

```php
public function ElephStamp->upgrade( CondorcetVote\ElephStamp\Receipt $receipt ): bool
```

## Description
Query the calendars for confirmations and merge them into the receipt.

Performs a single polling pass and returns whether anything changed; call
it again later to keep polling a still-pending receipt.

## Parameters

### **receipt:**
```php
CondorcetVote\ElephStamp\Receipt $receipt
```
**Type:** [`CondorcetVote\ElephStamp\Receipt`](../Receipt/class_Receipt.md)



## Return
**Type:** `bool`


