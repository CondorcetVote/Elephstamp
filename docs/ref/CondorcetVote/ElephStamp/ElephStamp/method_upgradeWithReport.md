> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method upgradeWithReport()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L292)

```php
public function ElephStamp->upgradeWithReport( CondorcetVote\ElephStamp\Receipt $receipt, [ bool $pollAll = false, bool $verify = true, int $requiredConfirmations = 6 ] ): CondorcetVote\ElephStamp\Upgrade\UpgradeReport
```

## Description
Like {@see upgrade()}, but returns what happened with every calendar.

The report lists one entry per pending attestation of the receipt, in
proof order: upgraded, still pending, failed, rejected, unconfirmed,
unverifiable, or skipped because its calendar is not whitelisted.

A calendar's answer is untrusted: a wrong or hostile calendar could
hand out an attestation naming a block that does not commit to the
proof, or a block too recent to be final, and the receipt would be
"complete" with a proof that fails verification. So by default every
Bitcoin attestation in an answer is verified exactly like
{@see \CondorcetVote\ElephStamp\verify()} does, through the configured {@see \CondorcetVote\ElephStamp\Verify\BlockHeaderSource},
before anything is merged: an answer whose block does not match is
{@see \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Rejected}, one whose block is still too shallow
is {@see \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unconfirmed}, one that could not be checked
is {@see \CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome::Unverifiable}; none of them is merged, and
the calendar stays pending to be polled again. Only fully verified
answers become part of the receipt. Pass $verify false to merge
whatever the calendars return, as the reference client does.

One Bitcoin attestation makes a receipt complete and verifiable, so by
default a complete receipt is not polled and yields an empty report.
With $pollAll the calendars still pending in a complete receipt are
polled too, and the submissions already confirmed are reported as such.

## Parameters

### **receipt:**
```php
CondorcetVote\ElephStamp\Receipt $receipt
```
**Type:** [`CondorcetVote\ElephStamp\Receipt`](../Receipt/class_Receipt.md)



### **pollAll:**
```php
bool $pollAll = false
```
**Type:** `bool`

also poll the calendars still pending in an already complete receipt

### **verify:**
```php
bool $verify = true
```
**Type:** `bool`

check each calendar's Bitcoin attestations against the block header source before merging them

### **requiredConfirmations:**
```php
int $requiredConfirmations = 6
```
**Type:** `int`

depth a block needs before its attestation is merged, when verifying

## Return
**Type:** [`CondorcetVote\ElephStamp\Upgrade\UpgradeReport`](../Upgrade/UpgradeReport/class_UpgradeReport.md)



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if $requiredConfirmations is below one_
