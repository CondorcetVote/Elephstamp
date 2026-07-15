> CondorcetVote \ [ElephStamp](../../readme.md) \ [HashOperation](class_HashOperation.md)
# Method deserializeHash()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/HashOperation.php#L81)

```php
final public static function HashOperation::deserializeHash( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self
```

## Description
Read a cryptographic hash operation; reject non-hash operations.

Used for the file-hash operation field of a detached timestamp, which
must be a cryptographic hash.

## Parameters

### **deserializer:**
```php
CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer
```
**Type:** [`CondorcetVote\ElephStamp\Serialization\Deserializer`](../../Serialization/Deserializer/class_Deserializer.md)



## Return
**Type:** [`CondorcetVote\ElephStamp\Operation\HashOperation`](class_HashOperation.md)


