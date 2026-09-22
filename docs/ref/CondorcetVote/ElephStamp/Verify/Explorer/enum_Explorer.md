> CondorcetVote \ [ElephStamp](../../readme.md) \ **Explorer**
# Enum Explorer
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Verify/Explorer.php#L16)

## Description
The public block explorers this library knows how to talk to.

All of them expose the Esplora HTTP API and need no authentication. Use
{@see \CondorcetVote\ElephStamp\Verify\EsploraBlockHeaderSource} directly for another Esplora instance, such
as a self-hosted one.
## Elements

### Public Constants
| Constant Name | Signature | Description |
| ------------- | ------------- | ------------- |
| Blockstream | `public const Blockstream = \CondorcetVote\ElephStamp\Verify\Explorer::Blockstream` | __ |
| DEFAULT | `public const CondorcetVote\ElephStamp\Verify\Explorer DEFAULT = \CondorcetVote\ElephStamp\Verify\Explorer::MempoolSpace` | _The explorer used when none is chosen._ |
| MempoolSpace | `public const MempoolSpace = \CondorcetVote\ElephStamp\Verify\Explorer::MempoolSpace` | __ |

### Public Properties
| Property Name | Description |
| ------------- | ------------- |
| [name(...)](property_name.md) | __ |
| [value(...)](property_value.md) | __ |

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [baseUrl(...)](method_baseUrl.md) | _Base URL of the explorer's Esplora API._ |
| [label(...)](method_label.md) | _Human-readable name, e.g. "mempool.space"._ |
| [source(...)](method_source.md) | _A header source backed by this explorer._ |


## Public Representation
```php
enum CondorcetVote\ElephStamp\Verify\Explorer: string implements UnitEnum, BackedEnum
{
    case MempoolSpace = "mempool";
    case Blockstream = "blockstream";
    // Constants
    public const Blockstream = \CondorcetVote\ElephStamp\Verify\Explorer::Blockstream;
    public const CondorcetVote\ElephStamp\Verify\Explorer DEFAULT = \CondorcetVote\ElephStamp\Verify\Explorer::MempoolSpace;
    public const MempoolSpace = \CondorcetVote\ElephStamp\Verify\Explorer::MempoolSpace;

    // Properties
    public protected(set) readonly string $name;
    public protected(set) readonly string $value;

    // Methods
    public function baseUrl( ): string;
    public function label( ): string;
    public function source( [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, ?float $timeout = null ] ): CondorcetVote\ElephStamp\Verify\EsploraBlockHeaderSource;

}
```

## Full Representation
```php
enum CondorcetVote\ElephStamp\Verify\Explorer: string implements UnitEnum, BackedEnum
{
    case MempoolSpace = "mempool";
    case Blockstream = "blockstream";
    // Constants
    public const Blockstream = \CondorcetVote\ElephStamp\Verify\Explorer::Blockstream;
    public const CondorcetVote\ElephStamp\Verify\Explorer DEFAULT = \CondorcetVote\ElephStamp\Verify\Explorer::MempoolSpace;
    public const MempoolSpace = \CondorcetVote\ElephStamp\Verify\Explorer::MempoolSpace;

    // Properties
    public protected(set) readonly string $name;
    public protected(set) readonly string $value;

    // Methods
    public function baseUrl( ): string;
    public function label( ): string;
    public function source( [ ?Symfony\Contracts\HttpClient\HttpClientInterface $httpClient = null, ?float $timeout = null ] ): CondorcetVote\ElephStamp\Verify\EsploraBlockHeaderSource;

}
```