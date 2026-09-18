<?php

declare(strict_types=1);

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
