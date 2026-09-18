<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console;

use CondorcetVote\ElephStamp\ElephStamp;

/**
 * Builds the {@see ElephStamp} client a command works with.
 *
 * This is the seam the CLI tests use to swap the network-backed client for a
 * fake one.
 */
interface ClientFactory
{
    public function create(ClientOptions $options): ElephStamp;
}
