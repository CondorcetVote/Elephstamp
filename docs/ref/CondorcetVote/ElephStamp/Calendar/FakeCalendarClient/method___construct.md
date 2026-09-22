> CondorcetVote \ [ElephStamp](../../readme.md) \ [FakeCalendarClient](class_FakeCalendarClient.md)
# Method __construct()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/FakeCalendarClient.php#L59)

```php
public function FakeCalendarClient->__construct( [ int $defaultBlockHeight = 800000, ?CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource $blocks = null ] )
```

## Parameters

### **defaultBlockHeight:**
```php
int $defaultBlockHeight = 800000
```
**Type:** `int`

height of the first block mined when {@see \CondorcetVote\ElephStamp\Calendar\confirm()} is given none; later ones follow it

### **blocks:**
```php
?CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource $blocks = null
```
**Type:** [`?CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource`](../../Verify/FakeBlockHeaderSource/class_FakeBlockHeaderSource.md)

the chain confirmations are mined into; a fresh one by default
