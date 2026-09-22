> CondorcetVote \ [ElephStamp](../../readme.md) \ [AnchorVerification](class_AnchorVerification.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/AnchorVerification.php#L19)

```php
public function AnchorVerification->__construct( CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor $anchor, CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome, [ ?CondorcetVote\ElephStamp\Verify\BlockHeader $header = null, ?int $confirmations = null, ?string $error = null ] )
```

## Parameters

### **anchor:**
```php
CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor $anchor
```
**Type:** [`CondorcetVote\ElephStamp\Bitcoin\BitcoinAnchor`](../../Bitcoin/BitcoinAnchor/class_BitcoinAnchor.md)



### **outcome:**
```php
CondorcetVote\ElephStamp\Verify\AnchorOutcome $outcome
```
**Type:** [`CondorcetVote\ElephStamp\Verify\AnchorOutcome`](../AnchorOutcome/enum_AnchorOutcome.md)



### **header:**
```php
?CondorcetVote\ElephStamp\Verify\BlockHeader $header = null
```
**Type:** [`?CondorcetVote\ElephStamp\Verify\BlockHeader`](../BlockHeader/class_BlockHeader.md)

the block header the source returned, when it did

### **confirmations:**
```php
?int $confirmations = null
```
**Type:** `?int`

blocks mined on top of this one, itself included; null when unknown

### **error:**
```php
?string $error = null
```
**Type:** `?string`

the source's error when {@see $outcome} is {@see \CondorcetVote\ElephStamp\Verify\AnchorOutcome::BlockUnavailable}
