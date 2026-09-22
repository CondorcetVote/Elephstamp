> CondorcetVote \ [ElephStamp](../../readme.md) \ **TransactionParser**
# Class TransactionParser
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Bitcoin/TransactionParser.php#L18)

## Description
Recognises the serialized form of a Bitcoin transaction.

A proof embeds the transaction that carries the commitment as raw bytes
(prepend/append operations around the commitment). Only the structure is
checked, not the semantics: the goal is to tell a transaction apart from
any other message on the proof path, such as a merkle node.

The witness-stripped serialization is expected: the transaction id is
computed over it, so that is what a proof must contain.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_ITEMS | `private const int MAX_ITEMS = 100000` | _Upper bound on inputs/outputs, guarding against absurd counts in hostile input before any allocation happens._ |
| MIN_LENGTH | `private const int MIN_LENGTH = 60` | _Smallest possible transaction: version, one input with an empty script, one output with an empty script, locktime._ |
| OUTPOINT_LENGTH | `private const int OUTPOINT_LENGTH = 36` | __ |
| SEQUENCE_LENGTH | `private const int SEQUENCE_LENGTH = 4` | __ |
| VALUE_LENGTH | `private const int VALUE_LENGTH = 8` | __ |

### Public Static Methods
| Method Name | Description |
| ------------- | ------------- |
| [isTransaction(...)](method_isTransaction.md) | _Whether $bytes is exactly one well-formed, witness-stripped transaction._ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Bitcoin\TransactionParser
{

    // Static Methods
    public static function isTransaction( string $bytes ): bool;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Bitcoin\TransactionParser
{
    // Constants
    private const int MAX_ITEMS = 100000;
    private const int MIN_LENGTH = 60;
    private const int OUTPOINT_LENGTH = 36;
    private const int SEQUENCE_LENGTH = 4;
    private const int VALUE_LENGTH = 8;

    // Static Methods
    public static function isTransaction( string $bytes ): bool;
    private static function readCompactSize( string $bytes, int &$offset ): ?int;
    private static function skipVarBytes( string $bytes, int &$offset ): bool;

}
```