> CondorcetVote \ [Receipt](class_Receipt.md)
# Method save()
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Receipt.php#L131)

```php
public function Receipt->save( ): void
```

## Description
Write the receipt back to the file it was loaded from or last saved to.

## Return
**Type:** `void`



## Throws
- **[\CondorcetVote\ElephStamp\Exception\InvalidInputException](../Exception/InvalidInputException/class_InvalidInputException.md)** _if the receipt has no {@see $path} yet, or the file cannot be written_
