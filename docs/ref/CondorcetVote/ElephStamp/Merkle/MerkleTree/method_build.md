> CondorcetVote \ [ElephStamp](../../readme.md) \ [MerkleTree](class_MerkleTree.md)
# Method build()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Merkle/MerkleTree.php#L49)

```php
public static function MerkleTree::build( array $leaves ): CondorcetVote\ElephStamp\Timestamp
```

## Description
Merkelize a list of leaf timestamps in place, returning the tip.

## Parameters

### **leaves:**
```php
array $leaves
```
**Type:** `array`



## Return
**Type:** [`CondorcetVote\ElephStamp\Timestamp`](../../Timestamp/class_Timestamp.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../../Exception/InvalidInputException/class_InvalidInputException.md)** _if the list is empty_
