> CondorcetVote \ [Timestamp](class_Timestamp.md)
# Method addAttestation()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L50)

```php
public function Timestamp->addAttestation( CondorcetVote\ElephStamp\Attestation\TimeAttestation $attestation ): bool
```

## Description
Attach an attestation to this node (deduplicated).

## Parameters

### **attestation:**
```php
CondorcetVote\ElephStamp\Attestation\TimeAttestation $attestation
```
**Type:** [`CondorcetVote\ElephStamp\Attestation\TimeAttestation`](../Attestation/TimeAttestation/class_TimeAttestation.md)



## Return
**Type:** `bool`

whether the attestation was not already present
