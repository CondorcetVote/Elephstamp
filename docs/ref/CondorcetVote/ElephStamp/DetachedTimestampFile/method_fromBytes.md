> CondorcetVote \ [DetachedTimestampFile](class_DetachedTimestampFile.md)
# Method fromBytes()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/DetachedTimestampFile.php#L71)

```php
public static function DetachedTimestampFile::fromBytes( string $bytes ): self
```

## Description
Parse the raw bytes of an `.ots` file.

## Parameters

### **bytes:**
```php
string $bytes
```
**Type:** `string`



## Return
**Type:** [`CondorcetVote\ElephStamp\DetachedTimestampFile`](class_DetachedTimestampFile.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\SerializationException](../Exception/SerializationException/class_SerializationException.md)** _on malformed input_
