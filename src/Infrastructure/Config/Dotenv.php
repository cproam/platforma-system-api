<?php declare(strict_types=1);

namespace App\Infrastructure\Config;

/**
 * Minimal dotenv loader to populate environment variables from a file.
 * Intended as a lightweight alternative when symfony/dotenv is unavailable.
 */
final class Dotenv
{
    /**
     * Load variables from the given file if it exists.
     */
    public function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $v = trim($v, "\"' ");
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}
