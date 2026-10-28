> CondorcetVote \ [ElephStamp](../../readme.md) \ [Verifier](class_Verifier.md)
# Method verifyMany()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/Verifier.php#L67)

```php
public function Verifier->verifyMany( array $receipts, [ array $files = [] ] ): array
```

## Description
Verify several receipts in one pass, related or not.

The chain tip is fetched once for the whole batch and each block header
once, however many receipts name it: receipts stamped together and
confirmed in the same block cost a single header, while unrelated
receipts simply add theirs. Every receipt still gets its own report,
aligned with $receipts.

Never throws for a source failure: the affected attestations are
reported as {@see \CondorcetVote\ElephStamp\Verify\AnchorOutcome::BlockUnavailable}.

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

the file each receipt should be the proof of, keyed like $receipts; a missing or null entry only checks the proof itself

## Return
**Type:** `array`

one per receipt, in the same order

## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _if $files names an index with no receipt_
