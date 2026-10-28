> CondorcetVote \ [ElephStamp](../../readme.md) \ [CalendarWhitelist](class_CalendarWhitelist.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/CalendarWhitelist.php#L48)

```php
public function CalendarWhitelist->__construct( array $patterns )
```

## Parameters

### **patterns:**
```php
array $patterns
```
**Type:** `array`



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _on a pattern that is not an https URL with a host, or whose glob is too broad to be anchored to a domain_
