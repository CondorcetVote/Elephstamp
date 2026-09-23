> CondorcetVote \ [ElephStamp](../../readme.md) \ [BitcoinRpcBlockHeaderSource](class_BitcoinRpcBlockHeaderSource.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/BitcoinRpcBlockHeaderSource.php#L81)

```php
public function BitcoinRpcBlockHeaderSource->__construct( string $url, [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, ?string $user = null, ?string $password = null, ?string $cookieFile = null, string $userAgent = 'ElephStamp', float $timeout = 10.0, float $maxDuration = 30.0, ?string $label = null ] )
```

## Parameters

### **url:**
```php
string $url
```
**Type:** `string`

e.g. `http://127.0.0.1:8332`, `http://user:password@127.0.0.1:8332`, or a provider's https endpoint

### **httpClient:**
```php
?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null
```
**Type:** `?Symfony\Contracts\HttpClient\HttpClientInterface`



### **user:**
```php
?string $user = null
```
**Type:** `?string`

RPC user (`rpcuser`), with $password

### **password:**
```php
?string $password = null
```
**Type:** `?string`

RPC password (`rpcpassword`), with $user

### **cookieFile:**
```php
?string $cookieFile = null
```
**Type:** `?string`

path to Bitcoin Core's `.cookie` file (e.g. `~/.bitcoin/.cookie`), instead of a user and password

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
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _on an unusable URL, plain http to a public host, or inconsistent credentials_
