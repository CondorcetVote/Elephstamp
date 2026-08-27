> CondorcetVote \ [Receipt](class_Receipt.md)
# Method fromSplFileObject()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Receipt.php#L75)

```php
public static function Receipt::fromSplFileObject( SplFileObject $file ): self
```

## Description
Load a receipt from an already-open, readable `.ots` file handle.

## Parameters

### **file:**
```php
SplFileObject $file
```
**Type:** `SplFileObject`



## Return
**Type:** [`CondorcetVote\ElephStamp\Receipt`](class_Receipt.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if the handle is not readable_
