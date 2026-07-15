> CondorcetVote \ [ElephStamp](../../readme.md) \ [Append](../Append/class_Append.md)
# Method fromTag()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/Operation.php#L93)

```php
final public static function Operation::fromTag( string $tag, CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer ): self
```

## Description
Build the operation identified by $tag, reading any argument it needs.

## Parameters

### **tag:**
```php
string $tag
```
**Type:** `string`



### **deserializer:**
```php
CondorcetVote\ElephStamp\Serialization\Deserializer $deserializer
```
**Type:** [`CondorcetVote\ElephStamp\Serialization\Deserializer`](../../Serialization/Deserializer/class_Deserializer.md)



## Return
**Type:** [`CondorcetVote\ElephStamp\Operation\Operation`](class_Operation.md)


