> CondorcetVote \ [ElephStamp](../../readme.md) \ [EsploraBlockHeaderSource](class_EsploraBlockHeaderSource.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/EsploraBlockHeaderSource.php#L44)

```php
public function EsploraBlockHeaderSource->__construct( string $baseUrl, [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, string $userAgent = 'ElephStamp', float $timeout = 10.0, float $maxDuration = 30.0, ?string $label = null ] )
```

## Parameters

### **baseUrl:**
```php
string $baseUrl
```
**Type:** `string`

e.g. `https://mempool.space/api`

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

idle timeout in seconds

### **maxDuration:**
```php
float $maxDuration = 30.0
```
**Type:** `float`

hard cap in seconds on a whole request

### **label:**
```php
?string $label = null
```
**Type:** `?string`

name shown to users; defaults to the host

## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _on a non-https base URL_
