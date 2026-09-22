> CondorcetVote \ [ElephStamp](../../readme.md) \ [FakeCalendarClient](class_FakeCalendarClient.md)
# Method confirm()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/FakeCalendarClient.php#L120)

```php
public function FakeCalendarClient->confirm( CondorcetVote\ElephStamp\Receipt $receipt, [ ?int $blockHeight = null, ?DateTimeImmutable $minedAt = null ] ): void
```

## Description
Confirm the commitments a specific receipt is pending on, as if Bitcoin
had included them in one block.

This resolves the receipt's own pending commitments, so it works
regardless of whether a privacy nonce was used. Commitments already
confirmed are left in their block.

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

the block to mine; the next free height by default

### **minedAt:**
```php
?DateTimeImmutable $minedAt = null
```
**Type:** `?DateTimeImmutable`

the block's time; a fixed date by default

## Return
**Type:** `void`



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _if that height is already mined with other commitments_
