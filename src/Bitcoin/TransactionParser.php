<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Bitcoin;

/**
 * Recognises the serialized form of a Bitcoin transaction.
 *
 * A proof embeds the transaction that carries the commitment as raw bytes
 * (prepend/append operations around the commitment). Only the structure is
 * checked, not the semantics: the goal is to tell a transaction apart from
 * any other message on the proof path, such as a merkle node.
 *
 * The witness-stripped serialization is expected: the transaction id is
 * computed over it, so that is what a proof must contain.
 */
final class TransactionParser
{
    /**
     * Smallest possible transaction: version, one input with an empty script,
     * one output with an empty script, locktime.
     */
    private const int MIN_LENGTH = 60;

    private const int OUTPOINT_LENGTH = 36;

    private const int SEQUENCE_LENGTH = 4;

    private const int VALUE_LENGTH = 8;

    /**
     * Upper bound on inputs/outputs, guarding against absurd counts in
     * hostile input before any allocation happens.
     */
    private const int MAX_ITEMS = 100_000;

    private function __construct() {}

    /**
     * Whether $bytes is exactly one well-formed, witness-stripped transaction.
     */
    public static function isTransaction(string $bytes): bool
    {
        $length = \strlen($bytes);

        if ($length < self::MIN_LENGTH) {
            return false;
        }

        $offset = 4; // version

        $inputs = self::readCompactSize($bytes, $offset);

        if ($inputs === null || $inputs < 1 || $inputs > self::MAX_ITEMS) {
            return false;
        }

        for ($i = 0; $i < $inputs; ++$i) {
            $offset += self::OUTPOINT_LENGTH;

            if (!self::skipVarBytes($bytes, $offset)) {
                return false;
            }

            $offset += self::SEQUENCE_LENGTH;
        }

        $outputs = self::readCompactSize($bytes, $offset);

        if ($outputs === null || $outputs < 1 || $outputs > self::MAX_ITEMS) {
            return false;
        }

        for ($i = 0; $i < $outputs; ++$i) {
            $offset += self::VALUE_LENGTH;

            if (!self::skipVarBytes($bytes, $offset)) {
                return false;
            }
        }

        $offset += 4; // locktime

        return $offset === $length;
    }

    /**
     * Read a Bitcoin CompactSize integer at $offset, advancing it.
     *
     * @return int|null null when truncated
     */
    private static function readCompactSize(string $bytes, int &$offset): ?int
    {
        if ($offset >= \strlen($bytes)) {
            return null;
        }

        $first = \ord($bytes[$offset]);
        ++$offset;

        [$width, $format] = match (true) {
            $first < 0xFD => [0, null],
            $first === 0xFD => [2, 'v'],
            $first === 0xFE => [4, 'V'],
            default => [8, 'P'],
        };

        if ($width === 0) {
            return $first;
        }

        if ($offset + $width > \strlen($bytes)) {
            return null;
        }

        $unpacked = unpack($format . 'value', $bytes, $offset);
        $offset += $width;

        if ($unpacked === false || !\is_int($unpacked['value']) || $unpacked['value'] < 0) {
            return null;
        }

        return $unpacked['value'];
    }

    /**
     * Skip a length-prefixed byte string (a script) at $offset.
     */
    private static function skipVarBytes(string $bytes, int &$offset): bool
    {
        if ($offset > \strlen($bytes)) {
            return false;
        }

        $length = self::readCompactSize($bytes, $offset);

        if ($length === null || $offset + $length > \strlen($bytes)) {
            return false;
        }

        $offset += $length;

        return true;
    }
}
