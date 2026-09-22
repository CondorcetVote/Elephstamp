> CondorcetVote \ [Receipt](class_Receipt.md)
# Method bitcoinAnchors()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Receipt.php#L280)

```php
public function Receipt->bitcoinAnchors( ): array
```

## Description
Every Bitcoin attestation with what the proof reveals about it: the
block merkle root it commits to and, when recoverable, the transaction
carrying the commitment (hence a transaction id to look up).

Like every Bitcoin figure this library reports, nothing is verified
against the blockchain.

## Return
**Type:** `array`


