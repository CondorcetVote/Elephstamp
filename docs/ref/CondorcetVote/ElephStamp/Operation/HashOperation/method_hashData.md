> CondorcetVote \ [ElephStamp](../../readme.md) \ [HashOperation](class_HashOperation.md)
# Method hashData()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Operation/HashOperation.php#L36)

```php
final public function HashOperation->hashData( string $data ): string
```

## Description
Hash a whole in-memory payload.

Unlike {@see \CondorcetVote\ElephStamp\Operation\Operation::apply()} this is not bound by the proof message
length limits: it hashes the original file content, which can be large.

## Parameters

### **data:**
```php
string $data
```
**Type:** `string`



## Return
**Type:** `string`


