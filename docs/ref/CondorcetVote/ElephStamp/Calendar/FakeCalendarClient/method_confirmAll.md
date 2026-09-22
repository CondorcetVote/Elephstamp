> CondorcetVote \ [ElephStamp](../../readme.md) \ [FakeCalendarClient](class_FakeCalendarClient.md)
# Method confirmAll()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/FakeCalendarClient.php#L138)

```php
public function FakeCalendarClient->confirmAll( [ ?int $blockHeight = null, ?DateTimeImmutable $minedAt = null ] ): void
```

## Description
Confirm every digest submitted so far and not confirmed yet, in one block.

## Parameters

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
