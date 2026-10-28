> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ [ProofInspection](class_ProofInspection.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Inspection/ProofInspection.php#L22)

```php
public function ProofInspection->__construct( CondorcetVote\ElephStamp\Receipt $receipt, array $submissions, array $anchors, array $unknownNotaries, int $sizeInBytes, [ ?CondorcetVote\ElephStamp\Console\Inspection\SubmissionPoint $submissionPoint = null ] )
```

## Parameters

### **receipt:**
```php
CondorcetVote\ElephStamp\Receipt $receipt
```
**Type:** [`CondorcetVote\ElephStamp\Receipt`](../../../Receipt/class_Receipt.md)



### **submissions:**
```php
array $submissions
```
**Type:** `array`



### **anchors:**
```php
array $anchors
```
**Type:** `array`



### **unknownNotaries:**
```php
array $unknownNotaries
```
**Type:** `array`



### **sizeInBytes:**
```php
int $sizeInBytes
```
**Type:** `int`

size of the serialized `.ots`

### **submissionPoint:**
```php
?CondorcetVote\ElephStamp\Console\Inspection\SubmissionPoint $submissionPoint = null
```
**Type:** [`?CondorcetVote\ElephStamp\Console\Inspection\SubmissionPoint`](../SubmissionPoint/class_SubmissionPoint.md)

the digest handed over to the calendars; null when the proof layout does not show it
