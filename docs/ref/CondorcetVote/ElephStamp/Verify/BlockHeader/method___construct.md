> CondorcetVote \ [ElephStamp](../../readme.md) \ [BlockHeader](class_BlockHeader.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/BlockHeader.php#L27)

```php
public function BlockHeader->__construct( int $height, string $hash, string $merkleRoot, DateTimeImmutable $time, [ ?string $rawHeader = null ] )
```

## Parameters

### **height:**
```php
int $height
```
**Type:** `int`



### **hash:**
```php
string $hash
```
**Type:** `string`

the block hash, internal byte order

### **merkleRoot:**
```php
string $merkleRoot
```
**Type:** `string`

the merkle root, internal byte order

### **time:**
```php
DateTimeImmutable $time
```
**Type:** `DateTimeImmutable`



### **rawHeader:**
```php
?string $rawHeader = null
```
**Type:** `?string`

the full 80-byte header when the source provided it
