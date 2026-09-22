> CondorcetVote \ [ElephStamp](../../readme.md) \ [VerificationReport](class_VerificationReport.md)
# Method mismatches()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/VerificationReport.php#L70)

```php
public function VerificationReport->mismatches( ): array
```

## Description
The attestations whose block does not commit to the proof, whatever
the verdict. Worth reporting even on a verified proof: one of its
calendars handed out something wrong.

## Return
**Type:** `array`


