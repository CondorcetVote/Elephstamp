> CondorcetVote \ [ElephStamp](../../readme.md) \ [Explorer](enum_Explorer.md)
# Method source()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/Explorer.php#L54)

```php
public function Explorer->source( [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, ?float $timeout = null ] ): CondorcetVote\ElephStamp\Verify\EsploraBlockHeaderSource
```

## Description
A header source backed by this explorer.

## Parameters

### **httpClient:**
```php
?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null
```
**Type:** `?Symfony\Contracts\HttpClient\HttpClientInterface`



### **timeout:**
```php
?float $timeout = null
```
**Type:** `?float`

seconds to wait for the explorer before giving up (idle and total); null for the defaults

## Return
**Type:** [`CondorcetVote\ElephStamp\Verify\EsploraBlockHeaderSource`](../EsploraBlockHeaderSource/class_EsploraBlockHeaderSource.md)


