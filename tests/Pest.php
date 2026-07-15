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
