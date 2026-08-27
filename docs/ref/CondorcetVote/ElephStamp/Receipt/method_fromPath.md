> CondorcetVote \ [Receipt](class_Receipt.md)
# Method fromPath()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Receipt.php#L49)

```php
public static function Receipt::fromPath( string $path ): self
```

## Description
Load a receipt from an `.ots` file on disk.

## Parameters

### **path:**
```php
string $path
```
**Type:** `string`



## Return
**Type:** [`CondorcetVote\ElephStamp\Receipt`](class_Receipt.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if the path is not a readable file_
