> CondorcetVote \ [ElephStamp](../../readme.md) \ [BitcoinTransaction](class_BitcoinTransaction.md)
# Method tryFromBytes()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Bitcoin/BitcoinTransaction.php#L31)

```php
public static function BitcoinTransaction::tryFromBytes( string $rawBytes ): ?self
```

## Description
Build from raw transaction bytes, computing the id.

## Parameters

### **rawBytes:**
```php
string $rawBytes
```
**Type:** `string`



## Return
**Type:** [`?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction`](class_BitcoinTransaction.md)

null when $rawBytes is not a well-formed transaction
