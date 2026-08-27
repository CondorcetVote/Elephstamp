> CondorcetVote \ [Timestamp](class_Timestamp.md)
# Method deserialize()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L287)

```php
public static function Timestamp::deserialize( CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer, ?string $initialMsg, [ int $recursionLimit = 256 ] ): self
```

## Description
Deserialize a timestamp for a known initial message.

The message is not stored in the format, so it must be supplied; it is
assumed correct and used to compute every operation result eagerly. A
null message deserializes an unverifiable subtree (below a keccak256
edge): its structure is preserved but its messages stay unknown.

## Parameters

### **deserializer:**
```php
CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer
```
**Type:** [`CondorcetVote\ElephStamp\Serialization\Deserializer`](../Serialization/Deserializer/class_Deserializer.md)



### **initialMsg:**
```php
?string $initialMsg
```
**Type:** `?string`



### **recursionLimit:**
```php
int $recursionLimit = 256
```
**Type:** `int`



## Return
**Type:** [`CondorcetVote\ElephStamp\Timestamp`](class_Timestamp.md)


