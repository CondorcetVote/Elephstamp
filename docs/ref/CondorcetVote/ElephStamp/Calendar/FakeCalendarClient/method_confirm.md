> CondorcetVote \ [ElephStamp](../../readme.md) \ [FakeCalendarClient](class_FakeCalendarClient.md)
# Method confirm()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/FakeCalendarClient.php#L82)

```php
public function FakeCalendarClient->confirm( CondorcetVote\ElephStamp\Receipt $receipt, [ ?int $blockHeight = null ] ): void
```

## Description
Confirm the commitments a specific receipt is pending on, as if Bitcoin
had included them.

This resolves the receipt's own pending commitments, so it works
regardless of whether a privacy nonce was used.

## Parameters

### **receipt:**
```php
CondorcetVote\ElephStamp\Receipt $receipt
```
**Type:** [`CondorcetVote\ElephStamp\Receipt`](../../Receipt/class_Receipt.md)



### **blockHeight:**
```php
?int $blockHeight = null
```
**Type:** `?int`



## Return
**Type:** `void`


