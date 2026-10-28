> CondorcetVote \ [ElephStamp](class_ElephStamp.md)
# Method upgradeMany()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/ElephStamp.php#L326)

```php
public function ElephStamp->upgradeMany( array $receipts, [ bool $pollAll = false, bool $verify = true, int $requiredConfirmations = 6 ] ): array
```

## Description
Upgrade several receipts in one pass, related or not.

Each receipt is polled and merged exactly as {@see \CondorcetVote\ElephStamp\upgradeWithReport()}
does, and gets its own report, aligned with $receipts. The pass itself
is shared: a calendar is asked once per distinct commitment, so the
receipts of a {@see \CondorcetVote\ElephStamp\stampMany()} batch, which all descend from the
same commitment, cost one request per calendar however many they are;
and every Bitcoin attestation the calendars return is checked in a
single batch, fetching the chain tip once and each block header once.
Receipts that have nothing in common simply add their own requests.

The receipts of a batch still in memory share their tree nodes, so an
answer merged through one of them reaches its siblings too; each
sibling then reports it as upgraded as well, since it did gain it in
this pass.

## Parameters

### **receipts:**
```php
array $receipts
```
**Type:** `array`



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
**Type:** `array`

one per receipt, in the same order; empty for no receipts

## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if $requiredConfirmations is below one_
