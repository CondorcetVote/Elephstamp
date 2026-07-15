> CondorcetVote \ [Receipt](class_Receipt.md)
# Method bitcoinBlockHeight()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Receipt.php#L194)

```php
public function Receipt->bitcoinBlockHeight( ): ?int
```

## Description
The lowest Bitcoin block height attesting the timestamp, or null if pending.

This library does not verify the attestation against the blockchain; it
merely reports what the proof claims.

## Return
**Type:** `?int`


