> CondorcetVote \ [ElephStamp](../../readme.md) \ [Verifier](class_Verifier.md)
# Method verify()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/Verifier.php#L43)

```php
public function Verifier->verify( CondorcetVote\ElephStamp\Receipt $receipt, [ ?CondorcetVote\ElephStamp\FileToStamp $file = null ] ): CondorcetVote\ElephStamp\Verify\VerificationReport
```

## Description
Verify a receipt, and optionally that it is the proof of a given file.

Never throws for a source failure: the affected attestations are
reported as {@see \CondorcetVote\ElephStamp\Verify\AnchorOutcome::BlockUnavailable}.

## Parameters

### **receipt:**
```php
CondorcetVote\ElephStamp\Receipt $receipt
```
**Type:** [`CondorcetVote\ElephStamp\Receipt`](../../Receipt/class_Receipt.md)



### **file:**
```php
?CondorcetVote\ElephStamp\FileToStamp $file = null
```
**Type:** [`?CondorcetVote\ElephStamp\FileToStamp`](../../FileToStamp/class_FileToStamp.md)



## Return
**Type:** [`CondorcetVote\ElephStamp\Verify\VerificationReport`](../VerificationReport/class_VerificationReport.md)


