> CondorcetVote \ [ElephStamp](../../readme.md) \ [Deserializer](class_Deserializer.md)
# Method readVarbytes()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Serialization/Deserializer.php#L90)

```php
public function Deserializer->readVarbytes( int $maxLength, [ int $minLength = 0 ] ): string
```

## Description
Read variable-length bytes: a varuint length prefix, then that many bytes.

## Parameters

### **maxLength:**
```php
int $maxLength
```
**Type:** `int`



### **minLength:**
```php
int $minLength = 0
```
**Type:** `int`



## Return
**Type:** `string`


