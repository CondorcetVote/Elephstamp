<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp\Console;

use CondorcetVote\ElephStamp\Status;

/**
 * Small presentation helpers shared by the commands.
 */
final class Formatter
{
    private const int ABBREVIATED_EDGE = 8;

    private function __construct() {}

    /**
     * Hex-encode raw bytes, abbreviated to `first8…last8` unless $full is set.
     */
    public static function hex(string $raw, bool $full = false): string
    {
        $hex = bin2hex($raw);

        return $full ? $hex : self::abbreviate($hex);
    }

    /**
     * Shorten a hex string to `first8…last8` when it is longer than that.
     */
    public static function abbreviate(string $hex): string
    {
        if (\strlen($hex) <= 2 * self::ABBREVIATED_EDGE + 1) {
            return $hex;
        }

        return substr($hex, 0, self::ABBREVIATED_EDGE) . '…' . substr($hex, -self::ABBREVIATED_EDGE);
    }

    /**
     * A coloured one-word status, e.g. "<fg=green>complete</>".
     */
    public static function status(Status $status): string
    {
        return match ($status) {
            Status::Complete => '<fg=green;options=bold>complete</>',
            Status::Pending => '<fg=yellow;options=bold>pending</>',
        };
    }

    /**
     * Lower-case machine name of a status, for JSON output.
     */
    public static function statusName(Status $status): string
    {
        return strtolower($status->name);
    }

    /**
     * Human-friendly byte count.
     */
    public static function bytes(int $bytes): string
    {
        return $bytes < 10_000 ? \sprintf('%d bytes', $bytes) : \sprintf('%.1f kB', $bytes / 1000);
    }

    /**
     * "1 file" / "3 files".
     */
    public static function plural(int $count, string $singular, ?string $plural = null): string
    {
        return \sprintf('%d %s', $count, $count === 1 ? $singular : ($plural ?? $singular . 's'));
    }
}
