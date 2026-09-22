> CondorcetVote \ [ElephStamp](../../readme.md) \ [ClientOptions](class_ClientOptions.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/ClientOptions.php#L28)

```php
public function ClientOptions->__construct( [ array $calendarUrls = [], ?int $requiredCalendars = null, array $whitelist = [], bool $useDefaultWhitelist = true, ?float $timeout = null, array $explorers = [], array $explorerUrls = [] ] )
```

## Parameters

### **calendarUrls:**
```php
array $calendarUrls = []
```
**Type:** `array`

calendars to submit to; empty for {@see \CondorcetVote\ElephStamp\ElephStamp::DEFAULT_CALENDAR_URLS}

### **requiredCalendars:**
```php
?int $requiredCalendars = null
```
**Type:** `?int`

the "m" of the m-of-n stamping policy; null for the library default

### **whitelist:**
```php
array $whitelist = []
```
**Type:** `array`

extra host patterns an upgrade may contact

### **useDefaultWhitelist:**
```php
bool $useDefaultWhitelist = true
```
**Type:** `bool`

whether {@see \CondorcetVote\ElephStamp\ElephStamp::DEFAULT_UPGRADE_WHITELIST} is kept alongside $whitelist

### **timeout:**
```php
?float $timeout = null
```
**Type:** `?float`

seconds to wait for a calendar or explorer before giving up; null for the library default

### **explorers:**
```php
array $explorers = []
```
**Type:** `array`

block explorers to verify against; all of them must agree when several are given

### **explorerUrls:**
```php
array $explorerUrls = []
```
**Type:** `array`

base URLs of additional Esplora-compatible explorers, e.g. a self-hosted one
