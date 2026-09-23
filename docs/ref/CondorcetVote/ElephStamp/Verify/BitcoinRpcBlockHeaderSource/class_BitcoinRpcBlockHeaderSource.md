> CondorcetVote \ [ElephStamp](../../readme.md) \ **BitcoinRpcBlockHeaderSource**
# Class BitcoinRpcBlockHeaderSource
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/BitcoinRpcBlockHeaderSource.php#L36)

## Description
Block headers from a Bitcoin node through its JSON-RPC interface: Bitcoin
Core (or anything speaking the same protocol, such as a hosted RPC
provider) answering `getblockhash`, `getblockheader` and `getblockcount`.

Only headers are asked for, so a pruned node is enough. As with the
explorer driver, the node is asked for the block hash at a height, then
for the raw 80-byte header of that hash; the header is parsed and its
proof of work checked locally, and its hash must be the one the node
announced.

Credentials are given one of three ways: `user:password@` in the URL,
the `$user`/`$password` arguments, or the path to the `.cookie` file
Bitcoin Core writes in its data directory. They never appear in error
messages or in {@see \CondorcetVote\ElephStamp\Verify\describe()}.

Plain `http` is accepted only for local and private hosts (`localhost`,
a single-label hostname such as a Docker service name, loopback, RFC 1918
and link-local addresses), where a node usually lives; any other host must
be reached over `https`. Redirects are never followed and answers are
size-capped.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| MAX_RESPONSE_BYTES | `private const int MAX_RESPONSE_BYTES = 8000` | _A raw header is 160 hex characters; the envelope and an error message fit comfortably in the rest._ |
| REQUEST_ID | `private const string REQUEST_ID = 'elephstamp'` | __ |
| RPC_INVALID_ADDRESS_OR_KEY | `private const int RPC_INVALID_ADDRESS_OR_KEY = -5` | _Bitcoin Core's RPC_INVALID_ADDRESS_OR_KEY: getblockheader of an unknown hash._ |
| RPC_INVALID_PARAMETER | `private const int RPC_INVALID_PARAMETER = -8` | _Bitcoin Core's RPC_INVALID_PARAMETER: getblockhash past the tip._ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [__construct(...)](method___construct.md) | __ |
| [blockHeader(...)](method_blockHeader.md) | __ |
| [describe(...)](method_describe.md) | __ |
| [tipHeight(...)](method_tipHeight.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Verify\BitcoinRpcBlockHeaderSource implements CondorcetVote\ElephStamp\Verify\BlockHeaderSource
{

    // Methods
    public function __construct( string $url, [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, ?string $user = null, ?string $password = null, ?string $cookieFile = null, string $userAgent = 'ElephStamp', float $timeout = 10.0, float $maxDuration = 30.0, ?string $label = null ] );
    public function blockHeader( int $height ): CondorcetVote\ElephStamp\Verify\BlockHeader;
    public function describe( ): string;
    public function tipHeight( ): int;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Verify\BitcoinRpcBlockHeaderSource implements CondorcetVote\ElephStamp\Verify\BlockHeaderSource
{
    // Constants
    private const int MAX_RESPONSE_BYTES = 8000;
    private const string REQUEST_ID = 'elephstamp';
    private const int RPC_INVALID_ADDRESS_OR_KEY = -5;
    private const int RPC_INVALID_PARAMETER = -8;

    // Properties
    private readonly ?string $credentials;
    private readonly string $host;
    private readonly Symfony\Contracts\HttpClient\HttpClientInterface $httpClient;
    private readonly ?string $label;
    private readonly float $maxDuration;
    private readonly float $timeout;
    private readonly string $url;
    private readonly string $userAgent;

    // Static Methods
    private static function isLocalHost( string $host ): bool;
    private static function readCookieFile( string $path ): string;
    private static function redact( string $url ): string;
    private static function resolveCredentials( array $urlParts, ?string $user, ?string $password, ?string $cookieFile ): ?string;

    // Methods
    public function __construct( string $url, [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, ?string $user = null, ?string $password = null, ?string $cookieFile = null, string $userAgent = 'ElephStamp', float $timeout = 10.0, float $maxDuration = 30.0, ?string $label = null ] );
    public function blockHeader( int $height ): CondorcetVote\ElephStamp\Verify\BlockHeader;
    public function describe( ): string;
    public function tipHeight( ): int;

}
```