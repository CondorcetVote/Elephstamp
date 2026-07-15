> CondorcetVote \ [FileToStamp](class_FileToStamp.md)
# Method fromDigest()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/FileToStamp.php#L54)

```php
public static function FileToStamp::fromDigest( string $digest ): self
```

## Description
Timestamp a digest that has already been computed elsewhere.

The digest is used verbatim (never re-hashed); its length must match the
hash operation the client is configured with (SHA-256 by default).

## Parameters

### **digest:**
```php
string $digest
```
**Type:** `string`



## Return
**Type:** [`CondorcetVote\ElephStamp\FileToStamp`](class_FileToStamp.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if the digest is empty_
