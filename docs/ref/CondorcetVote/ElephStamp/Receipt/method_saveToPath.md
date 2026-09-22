> CondorcetVote \ [Receipt](class_Receipt.md)
# Method saveToPath()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Receipt.php#L150)

```php
public function Receipt->saveToPath( string $path ): void
```

## Description
Write the receipt to an `.ots` file on disk, and remember that path
for later {@see save()} calls.

The bytes go through a temporary file renamed into place, so a crash
mid-write can never truncate an existing receipt — often the only copy
of a nonced commitment.

## Parameters

### **path:**
```php
string $path
```
**Type:** `string`



## Return
**Type:** `void`



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if the file cannot be written_
