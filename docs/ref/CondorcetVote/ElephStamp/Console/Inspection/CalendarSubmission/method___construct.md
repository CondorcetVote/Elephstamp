> CondorcetVote \ [ElephStamp](../../../readme.md) \ [Console](../../readme.md) \ [CalendarSubmission](class_CalendarSubmission.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/Inspection/CalendarSubmission.php#L21)

```php
public function CalendarSubmission->__construct( string $calendarUrl, ?string $commitment, ?DateTimeImmutable $recordedAt, bool $upgradable, array $confirmedBlockHeights )
```

## Parameters

### **calendarUrl:**
```php
string $calendarUrl
```
**Type:** `string`

the calendar that recorded the commitment

### **commitment:**
```php
?string $commitment
```
**Type:** `?string`

the raw digest the calendar holds; null below a non-computable operation

### **recordedAt:**
```php
?DateTimeImmutable $recordedAt
```
**Type:** `?DateTimeImmutable`

when the calendar recorded it, as encoded in the proof by the reference calendar server; null when the proof does not follow that layout

### **upgradable:**
```php
bool $upgradable
```
**Type:** `bool`

whether the calendar is on the upgrade whitelist

### **confirmedBlockHeights:**
```php
array $confirmedBlockHeights
```
**Type:** `array`

Bitcoin block heights already attached below this submission
