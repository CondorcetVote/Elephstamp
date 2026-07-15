> CondorcetVote \ [FileToStamp](class_FileToStamp.md)
# Method fromPath()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/FileToStamp.php#L82)

```php
public static function FileToStamp::fromPath( string $path ): self
```

## Description
Timestamp the file at the given path, read as a stream.

## Parameters

### **path:**
```php
string $path
```
**Type:** `string`



## Return
**Type:** [`CondorcetVote\ElephStamp\FileToStamp`](class_FileToStamp.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if the path is not a readable file_
