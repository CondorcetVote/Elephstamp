<?php

declare(strict_types=1);

// SymfonyStyle wraps blocks at the terminal width (capped at 120 columns), so
// where a sentence breaks depends on the terminal the suite runs in. Pin the
// width to make console output deterministic everywhere: IDE, CI, terminal.
putenv('COLUMNS=120');

/**
 * Path to a fresh writable temp file destroyed at the end of the test run.
 */
function makeTempPath(): string
{
    $path = tempnam(sys_get_temp_dir(), 'elephstamp-');

    if ($path === false) {
        throw new RuntimeException('Unable to create temp file.');
    }

    register_shutdown_function(static function () use ($path): void {
        if (is_file($path)) {
            @unlink($path);
        }
    });

    return $path;
}

/**
 * Path to a fresh temp directory destroyed at the end of the test run.
 */
function makeTempDir(): string
{
    $path = sys_get_temp_dir() . '/elephstamp-' . bin2hex(random_bytes(6));

    if (!mkdir($path, 0700)) {
        throw new RuntimeException('Unable to create temp directory.');
    }

    register_shutdown_function(static function () use ($path): void {
        foreach (glob($path . '/{,.}*', \GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($path);
    });

    return $path;
}

/**
 * Console output with all whitespace removed, so assertions on long paths
 * survive the line wrapping SymfonyStyle applies inside blocks.
 */
function unwrapped(string $display): string
{
    return (string) preg_replace('/\s+/', '', $display);
}

/**
 * The genuine header of Bitcoin block 967571 and its hash, as returned by
 * mempool.space. Shared by the header and explorer tests.
 */
const HEADER_967571 = '0060b62931171a874b5c03a881e68c7c0b273d237aae1cd0f04100000000000000000000abbfd07a03a3484f2abe54f23c20bbd9f33adb0dfcb4f19394be9f8e271d8f097e58ad6a5e3502171a8030e6';

const BLOCK_HASH_967571 = '00000000000000000000e32d9a295b892cdf1dcbdeb52461fb0f5e32cefa8364';
