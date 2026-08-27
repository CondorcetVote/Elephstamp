> CondorcetVote \ [Timestamp](class_Timestamp.md)
# Method allAttestations()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L149)

```php
public function Timestamp->allAttestations( ): array
```

## Description
Every attestation in the tree, paired with the message it commits to.

The message is null for attestations sitting below a non-computable
operation (keccak256).

## Return
**Type:** `array`


