> CondorcetVote \ [ElephStamp](../../readme.md) \ [FakeBlockHeaderSource](class_FakeBlockHeaderSource.md)
# Method setTipHeight()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/FakeBlockHeaderSource.php#L68)

```php
public function FakeBlockHeaderSource->setTipHeight( int $tipHeight ): void
```

## Description
Pretend the chain has grown to this height. Defaults to the highest
registered block plus enough for {@see Verifier::DEFAULT_REQUIRED_CONFIRMATIONS}.

## Parameters

### **tipHeight:**
```php
int $tipHeight
```
**Type:** `int`



## Return
**Type:** `void`


