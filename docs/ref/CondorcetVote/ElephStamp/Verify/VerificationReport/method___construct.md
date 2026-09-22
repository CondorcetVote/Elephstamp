> CondorcetVote \ [ElephStamp](../../readme.md) \ [VerificationReport](class_VerificationReport.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/VerificationReport.php#L21)

```php
public function VerificationReport->__construct( ?bool $fileMatches, array $anchors, int $requiredConfirmations, string $source )
```

## Parameters

### **fileMatches:**
```php
?bool $fileMatches
```
**Type:** `?bool`

whether the file's digest is the one the proof commits to; null when no file was given

### **anchors:**
```php
array $anchors
```
**Type:** `array`



### **requiredConfirmations:**
```php
int $requiredConfirmations
```
**Type:** `int`

the depth an attestation needed to count as verified

### **source:**
```php
string $source
```
**Type:** `string`

the header source that was consulted
