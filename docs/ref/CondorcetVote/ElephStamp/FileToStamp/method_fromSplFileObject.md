> CondorcetVote \ [FileToStamp](class_FileToStamp.md)
# Method fromSplFileObject()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/FileToStamp.php#L111)

```php
public static function FileToStamp::fromSplFileObject( SplFileObject $file ): self
```

## Description
Timestamp an already-open, readable file handle, read as a stream.

## Parameters

### **file:**
```php
SplFileObject $file
```
**Type:** `SplFileObject`



## Return
**Type:** [`CondorcetVote\ElephStamp\FileToStamp`](class_FileToStamp.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if the handle is not readable_
