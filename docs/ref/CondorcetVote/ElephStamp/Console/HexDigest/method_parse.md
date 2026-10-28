> CondorcetVote \ [ElephStamp](../../readme.md) \ [HexDigest](class_HexDigest.md)
# Method parse()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/HexDigest.php#L22)

```php
public static function HexDigest::parse( string $hex, CondorcetVote\ElephStamp\Operation\HashOperation $hashOperation, string $option ): string
```

## Description
The raw digest bytes, checked against the length the hash operation produces.

## Parameters

### **hex:**
```php
string $hex
```
**Type:** `string`



### **hashOperation:**
```php
CondorcetVote\ElephStamp\Operation\HashOperation $hashOperation
```
**Type:** [`CondorcetVote\ElephStamp\Operation\HashOperation`](../../Operation/HashOperation/class_HashOperation.md)



### **option:**
```php
string $option
```
**Type:** `string`

the option the value came from, for the error message

## Return
**Type:** `string`



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _when the value is not hex or has the wrong length_
