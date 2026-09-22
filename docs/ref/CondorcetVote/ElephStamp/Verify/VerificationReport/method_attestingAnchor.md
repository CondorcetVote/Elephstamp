> CondorcetVote \ [ElephStamp](../../readme.md) \ [VerificationReport](class_VerificationReport.md)
# Method attestingAnchor()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/VerificationReport.php#L84)

```php
public function VerificationReport->attestingAnchor( ): ?CondorcetVote\ElephStamp\Verify\AnchorVerification
```

## Description
The earliest block that verifies the proof, i.e. the one whose time is
the attested date. Null unless the verdict is {@see Verdict::Verified}.

## Return
**Type:** [`?CondorcetVote\ElephStamp\Verify\AnchorVerification`](../AnchorVerification/class_AnchorVerification.md)


