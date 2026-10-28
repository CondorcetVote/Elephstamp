> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method verifyMany()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L590)

```php
public function ElephStamp->verifyMany( array $receipts, [ array $files = [], int $requiredConfirmations = 6 ] ): array
```

## Description
Check several receipts against the blockchain in one pass, related or
not, fetching the chain tip once and each block header once however
many receipts name it. Each receipt gets its own report, aligned with
$receipts; see {@see Verifier::verifyMany()}.

## Parameters

### **receipts:**
```php
array $receipts
```
**Type:** `array`



### **files:**
```php
array $files = []
```
**Type:** `array`

the file each receipt should be the proof of, keyed like $receipts

### **requiredConfirmations:**
```php
int $requiredConfirmations = 6
```
**Type:** `int`

depth a block needs before its attestation counts as final

## Return
**Type:** `array`

one per receipt, in the same order; empty for no receipts

## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if $files names an index with no receipt_
