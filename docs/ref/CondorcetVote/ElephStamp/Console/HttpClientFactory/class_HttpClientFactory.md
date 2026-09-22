> CondorcetVote \ [ElephStamp](../../readme.md) \ **HttpClientFactory**
# Class HttpClientFactory
> [Read it at source](https://github.com/CondorcetVote/ElephStamp/blob/main/src/src/Console/HttpClientFactory.php#L14)

## Description
The production factory: talks to real calendars over HTTPS.
## Elements

### Public Methods
| Method Name | Description |
| ------------- | ------------- |
| [create(...)](method_create.md) | __ |


## Public Representation
```php
final class CondorcetVote\ElephStamp\Console\HttpClientFactory implements CondorcetVote\ElephStamp\Console\ClientFactory
{

    // Methods
    public function create( CondorcetVote\ElephStamp\Console\ClientOptions $options ): CondorcetVote\ElephStamp\ElephStamp;

}
```

## Full Representation
```php
final class CondorcetVote\ElephStamp\Console\HttpClientFactory implements CondorcetVote\ElephStamp\Console\ClientFactory
{

    // Static Methods
    private static function blockHeaderSource( CondorcetVote\ElephStamp\Console\ClientOptions $options ): CondorcetVote\ElephStamp\Verify\BlockHeaderSource;

    // Methods
    public function create( CondorcetVote\ElephStamp\Console\ClientOptions $options ): CondorcetVote\ElephStamp\ElephStamp;

}
```