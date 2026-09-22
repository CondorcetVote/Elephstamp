> CondorcetVote \ [ElephStamp](../../readme.md) \ [BitcoinAnchor](class_BitcoinAnchor.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Bitcoin/BitcoinAnchor.php#L23)

```php
public function BitcoinAnchor->__construct( CondorcetVote\ElephStamp\Attestation\BitcoinAttestation $attestation, ?string $merkleRoot, ?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction $transaction )
```

## Parameters

### **attestation:**
```php
CondorcetVote\ElephStamp\Attestation\BitcoinAttestation $attestation
```
**Type:** [`CondorcetVote\ElephStamp\Attestation\BitcoinAttestation`](../../Attestation/BitcoinAttestation/class_BitcoinAttestation.md)



### **merkleRoot:**
```php
?string $merkleRoot
```
**Type:** `?string`

the block merkle root the attestation commits to, in header byte order; null below a non-computable operation

### **transaction:**
```php
?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction $transaction
```
**Type:** [`?CondorcetVote\ElephStamp\Bitcoin\BitcoinTransaction`](../BitcoinTransaction/class_BitcoinTransaction.md)

the transaction carrying the commitment; null when the path does not embed a recognisable transaction followed by a merkle branch
