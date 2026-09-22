> CondorcetVote \ [ElephStamp](../../readme.md) \ [VerificationReport](class_VerificationReport.md)
# Method verdict()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/VerificationReport.php#L38)

```php
public function VerificationReport->verdict( ): CondorcetVote\ElephStamp\Verify\Verdict
```

## Description
The conclusion drawn from the file check and the attestations.

One verified attestation is enough: a proof is a bundle of independent
paths to the blockchain, so a path that does not match its block (a
calendar that answered nonsense, a corrupt branch) does not undo one
that does. Such mismatches stay visible in {@see $anchors} and in
{@see \CondorcetVote\ElephStamp\Verify\mismatches()}; only when nothing vouches for the proof does a
mismatch make the verdict {@see \CondorcetVote\ElephStamp\Verify\Verdict::Failed}.

## Return
**Type:** [`CondorcetVote\ElephStamp\Verify\Verdict`](../Verdict/enum_Verdict.md)


