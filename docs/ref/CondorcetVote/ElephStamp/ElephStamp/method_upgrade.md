> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method upgrade()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L255)

```php
public function ElephStamp->upgrade( CondorcetVote\ElephStamp\Receipt $receipt, [ bool $pollAll = false, bool $verify = true, int $requiredConfirmations = 6 ] ): bool
```

## Description
Query the calendars for confirmations and merge them into the receipt.

Performs a single polling pass and returns whether anything changed; call
it again later to keep polling a still-pending receipt. Use
{@see \CondorcetVote\ElephStamp\upgradeWithReport()} to learn what each calendar answered.

By default every Bitcoin attestation a calendar returns is checked
against the blockchain before it is merged, see {@see \CondorcetVote\ElephStamp\upgradeWithReport()}.

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

also poll the calendars still pending in an already complete receipt, to collect every attestation rather than stopping at the first

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
**Type:** `bool`


