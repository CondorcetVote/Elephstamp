> CondorcetVote \ [ElephStamp](../../readme.md) \ [CalendarUpgradeResult](class_CalendarUpgradeResult.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Upgrade/CalendarUpgradeResult.php#L21)

```php
public function CalendarUpgradeResult->__construct( string $calendarUrl, string $commitment, CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome, [ ?string $error = null, ?int $blockHeight = null, array $verifications = [] ] )
```

## Parameters

### **calendarUrl:**
```php
string $calendarUrl
```
**Type:** `string`

the calendar that was (or would have been) polled

### **commitment:**
```php
string $commitment
```
**Type:** `string`

the raw digest the calendar was asked about

### **outcome:**
```php
CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome $outcome
```
**Type:** [`CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome`](../UpgradeOutcome/enum_UpgradeOutcome.md)



### **error:**
```php
?string $error = null
```
**Type:** `?string`

the calendar's error message when {@see $outcome} is {@see \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Failed} or {@see \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Rejected}, or why it could not be checked when it is {@see \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unverifiable}

### **blockHeight:**
```php
?int $blockHeight = null
```
**Type:** `?int`

the lowest Bitcoin block height now attested below this submission, when there is one

### **verifications:**
```php
array $verifications = []
```
**Type:** `array`

how each Bitcoin attestation in the calendar's answer fared against the blockchain; empty when the answer carried none or verification was disabled
