> CondorcetVote \ [ElephStamp](../../readme.md) \ [HttpCalendarClient](class_HttpCalendarClient.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/HttpCalendarClient.php#L41)

```php
public function HttpCalendarClient->__construct( [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, string $userAgent = 'ElephStamp', float $timeout = 10.0, float $maxDuration = 30.0 ] )
```

## Parameters

### **httpClient:**
```php
?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null
```
**Type:** `?Symfony\Contracts\HttpClient\HttpClientInterface`



### **userAgent:**
```php
string $userAgent = 'ElephStamp'
```
**Type:** `string`



### **timeout:**
```php
float $timeout = 10.0
```
**Type:** `float`

idle timeout in seconds: how long a calendar may go silent

### **maxDuration:**
```php
float $maxDuration = 30.0
```
**Type:** `float`

hard cap in seconds on a whole request, however slowly the calendar drips bytes
