> CondorcetVote \ [Timestamp](class_Timestamp.md)
# Method findPending()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Timestamp.php#L163)

```php
public function Timestamp->findPending( ): array
```

## Description
The shallowest nodes that carry a pending attestation.

These are the nodes whose message must be re-submitted to a calendar to
upgrade the timestamp. A node that already carries any attestation stops
the descent, mirroring the reference client.

## Return
**Type:** `array`


