> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method verify()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L497)

```php
public function ElephStamp->verify( CondorcetVote\ElephStamp\Receipt $receipt, [ ?CondorcetVote\ElephStamp\FileToStamp $file = null, int $requiredConfirmations = 6 ] ): CondorcetVote\ElephStamp\Verify\VerificationReport
```

## Description
Check a receipt against the blockchain, through the configured block
header source, and optionally that it is the proof of the given file.

See {@see \CondorcetVote\ElephStamp\Verify\Verifier} for what is checked and what the verdict means.

## Parameters

### **receipt:**
```php
CondorcetVote\ElephStamp\Receipt $receipt
```
**Type:** [`CondorcetVote\ElephStamp\Receipt`](../Receipt/class_Receipt.md)



### **file:**
```php
?CondorcetVote\ElephStamp\FileToStamp $file = null
```
**Type:** [`?CondorcetVote\ElephStamp\FileToStamp`](../FileToStamp/class_FileToStamp.md)



### **requiredConfirmations:**
```php
int $requiredConfirmations = 6
```
**Type:** `int`

depth a block needs before its attestation counts as final

## Return
**Type:** [`CondorcetVote\ElephStamp\Verify\VerificationReport`](../Verify/VerificationReport/class_VerificationReport.md)


