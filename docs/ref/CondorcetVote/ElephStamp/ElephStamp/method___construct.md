> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L80)

```php
public function ElephStamp->__construct( [ ?CondorcetVote\ElephStamp\Calendar\CalendarClient $calendarClient = null, ?array $calendarUrls = null, ?int $requiredCalendars = null, ?CondorcetVote\ElephStamp\Operation\HashOperation $hashOperation = null, ?CondorcetVote\ElephStamp\Random\RandomSource $randomSource = null, ?array $upgradeWhitelist = null ] )
```

## Parameters

### **calendarClient:**
```php
?CondorcetVote\ElephStamp\Calendar\CalendarClient $calendarClient = null
```
**Type:** `?CondorcetVote\ElephStamp\Calendar\CalendarClient`



### **calendarUrls:**
```php
?array $calendarUrls = null
```
**Type:** `?array`

calendars to submit to (defaults to {@see \CondorcetVote\ElephStamp\DEFAULT_CALENDAR_URLS})

### **requiredCalendars:**
```php
?int $requiredCalendars = null
```
**Type:** `?int`

minimum number of calendars that must accept a stamp (the "m" of
m-of-n); defaults to 2 — like the reference client — or to 1 when
a single calendar is configured

### **hashOperation:**
```php
?CondorcetVote\ElephStamp\Operation\HashOperation $hashOperation = null
```
**Type:** [`?CondorcetVote\ElephStamp\Operation\HashOperation`](../Operation/HashOperation/class_HashOperation.md)



### **randomSource:**
```php
?CondorcetVote\ElephStamp\Random\RandomSource $randomSource = null
```
**Type:** `?CondorcetVote\ElephStamp\Random\RandomSource`



### **upgradeWhitelist:**
```php
?array $upgradeWhitelist = null
```
**Type:** `?array`

host patterns an upgrade may contact (defaults to {@see \CondorcetVote\ElephStamp\DEFAULT_UPGRADE_WHITELIST}); pass your own when using private calendars
