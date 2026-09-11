<?php
declare(strict_types=1);

function env(string $key, ?string $default = null): ?string
{
    static $vars = null;
    $realEnv = getenv($key);
    if ($realEnv !== false && $realEnv !== '') {
        return $realEnv;
    }
    if ($vars === null) {
        $vars = [];
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $vars[trim($k)] = trim($v, " \t\"'");
            }
        }
    }
    return $vars[$key] ?? $default;
}
