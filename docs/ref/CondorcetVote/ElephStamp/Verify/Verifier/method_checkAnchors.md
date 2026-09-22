> CondorcetVote \ [ElephStamp](../../readme.md) \ [Verifier](class_Verifier.md)
# Method checkAnchors()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/Verifier.php#L68)

```php
public function Verifier->checkAnchors( array $anchors ): array
```

## Description
Check Bitcoin attestations against the blocks they name, whatever
proof they come from: a receipt, or a calendar's answer about to be
merged into one.

The chain tip is fetched once for the whole batch, then one header per
attestation. Never throws for a source failure: the affected
attestations are reported as {@see \CondorcetVote\ElephStamp\Verify\AnchorOutcome::BlockUnavailable}.

## Parameters

### **anchors:**
```php
array $anchors
```
**Type:** `array`



## Return
**Type:** `array`

aligned with $anchors
