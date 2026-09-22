> CondorcetVote \ [ElephStamp](../../readme.md) \ [BlockHeader](class_BlockHeader.md)
# Method fromRawHeader()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/BlockHeader.php#L52)

```php
public static function BlockHeader::fromRawHeader( int $height, string $rawHeader ): self
```

## Description
Parse a raw 80-byte header, computing its hash and checking that the
hash satisfies the difficulty the header itself declares.

A source that hands over a raw header therefore cannot forge a merkle
root without also producing a valid proof of work for it.

## Parameters

### **height:**
```php
int $height
```
**Type:** `int`



### **rawHeader:**
```php
string $rawHeader
```
**Type:** `string`



## Return
**Type:** [`CondorcetVote\ElephStamp\Verify\BlockHeader`](class_BlockHeader.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\BlockSourceException](../../Exception/BlockSourceException/class_BlockSourceException.md)** _on a malformed header or a failed proof-of-work check_
