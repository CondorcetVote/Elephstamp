> CondorcetVote \ [ElephStamp](../../readme.md) \ [HashOperation](class_HashOperation.md)
# Method fromName()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/HashOperation.php#L36)

```php
public static function HashOperation::fromName( string $name ): self
```

## Description
The hash operation with the given name, as {@see describe()} returns it.

## Parameters

### **name:**
```php
string $name
```
**Type:** `string`



## Return
**Type:** [`CondorcetVote\ElephStamp\Operation\HashOperation`](class_HashOperation.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _on an unknown name_
