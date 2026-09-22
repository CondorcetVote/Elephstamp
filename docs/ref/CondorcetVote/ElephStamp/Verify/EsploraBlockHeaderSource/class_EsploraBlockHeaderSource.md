> CondorcetVote \ [ElephStamp](../../readme.md) \ **EsploraBlockHeaderSource**
# Class EsploraBlockHeaderSource
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/EsploraBlockHeaderSource.php#L25)

## Description
Block headers from any server speaking the Esplora HTTP API: mempool.space,
blockstream.info, or a self-hosted instance.

The source is asked for the block hash at a height, then for the raw
header of that hash. The header is parsed and its proof of work checked
locally, and the hash it produces must be the one the source announced:
an explorer cannot make a bogus merkle root pass without forging a valid
header for it.

The explorer is untrusted input, so the transport is hardened like the
calendar client: https only, no redirects, small response cap, timeouts.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_RESPONSE_BYTES | `private const int MAX_RESPONSE_BYTES = 1000` | _Every answer we read is a short hex string or a number._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [blockHeader(...)](method_blockHeader.md) | __ |
| [describe(...)](method_describe.md) | __ |
| [tipHeight(...)](method_tipHeight.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Verify\EsploraBlockHeaderSource implements CondorcetVote\ElephStamp\Verify\BlockHeaderSource
{

    // Methods
    public function __construct( string $baseUrl, [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, string $userAgent = 'ElephStamp', float $timeout = 10.0, float $maxDuration = 30.0, ?string $label = null ] );
    public function blockHeader( int $height ): CondorcetVote\ElephStamp\Verify\BlockHeader;
    public function describe( ): string;
    public function tipHeight( ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Verify\EsploraBlockHeaderSource implements CondorcetVote\ElephStamp\Verify\BlockHeaderSource
{
    // Constants
    private const int MAX_RESPONSE_BYTES = 1000;

    // Properties
    private readonly string $baseUrl;
    private readonly Symfony\Contracts\HttpClient\HttpClientInterface $httpClient;
    private readonly ?string $label;
    private readonly float $maxDuration;
    private readonly float $timeout;
    private readonly string $userAgent;

    // Methods
    public function __construct( string $baseUrl, [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, string $userAgent = 'ElephStamp', float $timeout = 10.0, float $maxDuration = 30.0, ?string $label = null ] );
    public function blockHeader( int $height ): CondorcetVote\ElephStamp\Verify\BlockHeader;
    public function describe( ): string;
    public function tipHeight( ): int;

}
```