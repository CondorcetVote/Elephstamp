<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console;

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Operation\HashOperation;

/**
 * Parses a digest given on the command line as hexadecimal.
 */
final class HexDigest
{
    /**
     * The raw digest bytes, checked against the length the hash operation produces.
     *
     * @param string $option the option the value came from, for the error message
     *
     * @throws InvalidInputException when the value is not hex or has the wrong length
     */
    public static function parse(string $hex, HashOperation $hashOperation, string $option): string
    {
        $expectedLength = $hashOperation->digestLength() * 2;
        $hex = strtolower($hex);
        $raw = ctype_xdigit($hex) && \strlen($hex) === $expectedLength ? hex2bin($hex) : false;

        if ($raw === false) {
            throw new InvalidInputException(\sprintf('%s must be a %d-character hex %s digest', $option, $expectedLength, $hashOperation->describe()));
        }

        return $raw;
    }
}
