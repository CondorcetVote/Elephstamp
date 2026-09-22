> CondorcetVote \ [ElephStamp](../../readme.md) \ **FakeCalendarClient**
# Class FakeCalendarClient
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Calendar/FakeCalendarClient.php#L34)

## Description
In-memory calendar for tests and local integration environments.

Behaves like a real calendar without any network: every submitted digest
starts pending, and the test drives it to completion by calling
{@see \CondorcetVote\ElephStamp\Calendar\confirm()} or {@see \CondorcetVote\ElephStamp\Calendar\confirmAll()}, simulating Bitcoin confirmation.

Confirming mines a block into a {@see \CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource}: the
commitments confirmed together are bound in a merkle tree, the block's
merkle root is that tree's root, and each commitment gets its path to it,
like a real calendar aggregating its clients into one transaction. A
client verifying against that same source ({@see \CondorcetVote\ElephStamp\ElephStamp::fake()}
wires it) therefore upgrades and verifies a confirmed receipt without
further setup. A block is immutable once mined, so confirming more
commitments later takes another height: the next free one by default.

Responses are deterministic, so a stamp produced with a
{@see \CondorcetVote\ElephStamp\Random\DeterministicRandomSource} is fully
reproducible.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| DEFAULT_BLOCK_HEIGHT | `public const int DEFAULT_BLOCK_HEIGHT = 800000` | _Block height of the first block mined, when no height is given._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [blocks(...)](method_blocks.md) | _The fake chain this calendar mines its confirmations into. Verify against it, or tamper with it to simulate a calendar that lies._ |
| [confirm(...)](method_confirm.md) | _Confirm the commitments a specific receipt is pending on, as if Bitcoin had included them in one block._ |
| [confirmAll(...)](method_confirmAll.md) | _Confirm every digest submitted so far and not confirmed yet, in one block._ |
| [getTimestamps(...)](method_getTimestamps.md) | __ |
| [reset(...)](method_reset.md) | _Forget all submitted and confirmed state, and the blocks mined so far._ |
| [submit(...)](method_submit.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Calendar\FakeCalendarClient implements CondorcetVote\ElephStamp\Calendar\CalendarClient
{
    // Constants
    public const int DEFAULT_BLOCK_HEIGHT = 800000;

    // Methods
    public function __construct( [ int $defaultBlockHeight = 800000, ?CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource $blocks = null ] );
    public function blocks( ): CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource;
    public function confirm( CondorcetVote\ElephStamp\Receipt $receipt, [ ?int $blockHeight = null, ?DateTimeImmutable $minedAt = null ] ): void;
    public function confirmAll( [ ?int $blockHeight = null, ?DateTimeImmutable $minedAt = null ] ): void;
    public function getTimestamps( array $requests ): array;
    public function reset( ): void;
    public function submit( array $calendarUrls, string $digest ): array;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Calendar\FakeCalendarClient implements CondorcetVote\ElephStamp\Calendar\CalendarClient
{
    // Constants
    public const int DEFAULT_BLOCK_HEIGHT = 800000;

    // Properties
    private readonly CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource $blocks;
    private array $confirmed = [];
    private readonly int $defaultBlockHeight;
    private ?int $lastMinedHeight = null;
    private array $submitted = [];

    // Methods
    public function __construct( [ int $defaultBlockHeight = 800000, ?CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource $blocks = null ] );
    public function blocks( ): CondorcetVote\ElephStamp\Verify\FakeBlockHeaderSource;
    public function confirm( CondorcetVote\ElephStamp\Receipt $receipt, [ ?int $blockHeight = null, ?DateTimeImmutable $minedAt = null ] ): void;
    public function confirmAll( [ ?int $blockHeight = null, ?DateTimeImmutable $minedAt = null ] ): void;
    public function getTimestamps( array $requests ): array;
    public function reset( ): void;
    public function submit( array $calendarUrls, string $digest ): array;

}
```