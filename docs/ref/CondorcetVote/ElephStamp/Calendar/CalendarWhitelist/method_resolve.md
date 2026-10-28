> CondorcetVote \ [ElephStamp](../../readme.md) \ [CalendarWhitelist](class_CalendarWhitelist.md)
# Method resolve()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/CalendarWhitelist.php#L88)

```php
public function CalendarWhitelist->resolve( string $url ): ?string
```

## Description
The normalized URL to contact for $url, or null when it is not allowed.

Send requests to this URL rather than to $url itself: it is the form
the whitelist actually checked, so no difference between two URL
parsers can make the request reach another host.

## Parameters

### **url:**
```php
string $url
```
**Type:** `string`



## Return
**Type:** `?string`


