<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Verify;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The public block explorers this library knows how to talk to.
 *
 * All of them expose the Esplora HTTP API and need no authentication. Use
 * {@see EsploraBlockHeaderSource} directly for another Esplora instance, such
 * as a self-hosted one.
 */
enum Explorer: string
{
    case MempoolSpace = 'mempool';

    case Blockstream = 'blockstream';

    /**
     * The explorer used when none is chosen.
     */
    public const self DEFAULT = self::MempoolSpace;

    /**
     * Base URL of the explorer's Esplora API.
     */
    public function baseUrl(): string
    {
        return match ($this) {
            self::MempoolSpace => 'https://mempool.space/api',
            self::Blockstream => 'https://blockstream.info/api',
        };
    }

    /**
     * Human-readable name, e.g. "mempool.space".
     */
    public function label(): string
    {
        return match ($this) {
            self::MempoolSpace => 'mempool.space',
            self::Blockstream => 'blockstream.info',
        };
    }

    /**
     * A header source backed by this explorer.
     *
     * @param float|null $timeout seconds to wait for the explorer before giving up (idle and total); null for the defaults
     */
    public function source(?HttpClientInterface $httpClient = null, ?float $timeout = null): EsploraBlockHeaderSource
    {
        return $timeout === null
            ? new EsploraBlockHeaderSource($this->baseUrl(), $httpClient, label: $this->label())
            : new EsploraBlockHeaderSource($this->baseUrl(), $httpClient, timeout: $timeout, maxDuration: $timeout, label: $this->label());
    }
}
