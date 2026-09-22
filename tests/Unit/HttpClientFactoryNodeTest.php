<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Console\{ClientOptions, HttpClientFactory};
use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Verify\Explorer;

it('builds a client from node options and surfaces bad node settings', function (): void {
    $factory = new HttpClientFactory;

    expect($factory->create(new ClientOptions(node: 'http://127.0.0.1:8332')))->toBeInstanceOf(CondorcetVote\ElephStamp\ElephStamp::class)
        ->and($factory->create(new ClientOptions(node: 'http://127.0.0.1:8332', nodeUser: 'a', nodePassword: 'b', explorers: [Explorer::Blockstream], timeout: 3.0)))->toBeInstanceOf(CondorcetVote\ElephStamp\ElephStamp::class)
        ->and(new ClientOptions(node: 'http://127.0.0.1:8332')->resolvedExplorers())->toBe([])
        ->and(new ClientOptions()->resolvedExplorers())->toBe([Explorer::DEFAULT]);

    $factory->create(new ClientOptions(node: 'http://rpc.example.com:8332'));
})->throws(InvalidInputException::class, 'use https to reach rpc.example.com');
