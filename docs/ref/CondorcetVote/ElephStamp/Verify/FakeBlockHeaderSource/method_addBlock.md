> CondorcetVote \ [ElephStamp](../../readme.md) \ [FakeBlockHeaderSource](class_FakeBlockHeaderSource.md)
# Method addBlock()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/FakeBlockHeaderSource.php#L37)

```php
public function FakeBlockHeaderSource->addBlock( int $height, string $merkleRoot, [ ?DateTimeImmutable $time = null ] ): void
```

## Description
Register a block. Its hash is derived from the height, since nothing
here is real.

## Parameters

### **height:**
```php
int $height
```
**Type:** `int`



### **merkleRoot:**
```php
string $merkleRoot
```
**Type:** `string`

32 bytes, internal byte order

### **time:**
```php
?DateTimeImmutable $time = null
```
**Type:** `?DateTimeImmutable`



## Return
**Type:** `void`



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _if the height already holds a different merkle root_
