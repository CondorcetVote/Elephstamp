> CondorcetVote \ [ElephStamp](../../readme.md) \ [TimeAttestation](class_TimeAttestation.md)
# Method compareTo()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Attestation/TimeAttestation.php#L46)

```php
public function TimeAttestation->compareTo( self $other ): int
```

## Description
Order two attestations deterministically.

Attestations of the same type are ordered by their own criteria; those
of different types are ordered by tag, matching the reference
implementation's canonical ordering.

## Parameters

### **other:**
```php
self $other
```
**Type:** [`CondorcetVote\ElephStamp\Attestation\TimeAttestation`](class_TimeAttestation.md)



## Return
**Type:** `int`


