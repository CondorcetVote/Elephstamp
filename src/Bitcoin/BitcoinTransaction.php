<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Bitcoin;

/**
 * The Bitcoin transaction carrying a commitment, as recovered from a proof.
 *
 * A proof does not name the transaction: it embeds its raw bytes around the
 * commitment and hashes them on the way to the block's merkle root. This
 * class is the result of recognising that step, giving a transaction id that
 * can be looked up in any block explorer.
 */
final class BitcoinTransaction
{
    /**
     * @param string $rawBytes the witness-stripped serialization embedded in the proof
     * @param string $txid     the double-SHA-256 of $rawBytes, in internal byte order
     */
    public function __construct(
        public readonly string $rawBytes,
        public readonly string $txid,
    ) {}

    /**
     * Build from raw transaction bytes, computing the id.
     *
     * @return self|null null when $rawBytes is not a well-formed transaction
     */
    public static function tryFromBytes(string $rawBytes): ?self
    {
        if (!TransactionParser::isTransaction($rawBytes)) {
            return null;
        }

        return new self($rawBytes, hash('sha256', hash('sha256', $rawBytes, binary: true), binary: true));
    }

    /**
     * The transaction id as block explorers display it (byte-reversed hex).
     */
    public function txidHex(): string
    {
        return bin2hex(strrev($this->txid));
    }

    /**
     * Size of the witness-stripped transaction, in bytes.
     */
    public function size(): int
    {
        return \strlen($this->rawBytes);
    }
}
