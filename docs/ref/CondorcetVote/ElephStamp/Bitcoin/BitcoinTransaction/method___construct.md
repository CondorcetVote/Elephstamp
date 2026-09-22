> CondorcetVote \ [ElephStamp](../../readme.md) \ [BitcoinTransaction](class_BitcoinTransaction.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Bitcoin/BitcoinTransaction.php#L21)

```php
public function BitcoinTransaction->__construct( string $rawBytes, string $txid )
```

## Parameters

### **rawBytes:**
```php
string $rawBytes
```
**Type:** `string`

the witness-stripped serialization embedded in the proof

### **txid:**
```php
string $txid
```
**Type:** `string`

the double-SHA-256 of $rawBytes, in internal byte order
