> CondorcetVote \ [ElephStamp](../../readme.md) \ [MerkleTree](class_MerkleTree.md)
# Method concatenateThenSha256()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Merkle/MerkleTree.php#L27)

```php
public static function MerkleTree::concatenateThenSha256( CondorcetVote\ElephStamp\Timestamp $left, CondorcetVote\ElephStamp\Timestamp $right ): CondorcetVote\ElephStamp\Timestamp
```

## Description
Combine two timestamps: append/prepend to reach the same concatenated
message, then hash it. Returns the timestamp of the parent node.

## Parameters

### **left:**
```php
CondorcetVote\ElephStamp\Timestamp $left
```
**Type:** [`CondorcetVote\ElephStamp\Timestamp`](../../Timestamp/class_Timestamp.md)



### **right:**
```php
CondorcetVote\ElephStamp\Timestamp $right
```
**Type:** [`CondorcetVote\ElephStamp\Timestamp`](../../Timestamp/class_Timestamp.md)



## Return
**Type:** [`CondorcetVote\ElephStamp\Timestamp`](../../Timestamp/class_Timestamp.md)


