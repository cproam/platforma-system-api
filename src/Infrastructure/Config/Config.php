<?php declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Infrastructure\Config\Dotenv;

final class Config
{
    private static ?array $cache = null;
    private static bool $envLoaded = false;
    private static ?Dotenv $dotenv = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::load();
        $segments = explode('.', $key);
        $value = $config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public static function dbPath(): string
    {
        return (string) self::get('db.path', dirname(__DIR__, 3) . '/var/data.sqlite');
    }

    public static function env(string $name, ?string $default = null): ?string
    {
        self::ensureEnvLoaded();
        $val = getenv($name);
        return $val === false ? $default : $val;
    }

    public static function jwtSecret(): string
    {
        return self::env('JWT_SECRET') ?? (string) self::get('jwt.secret', 'dev-secret-change-me');
    }

    public static function jwtTtl(): int
    {
        $val = self::env('JWT_TTL');
        return $val !== null ? (int)$val : (int) self::get('jwt.ttl', 3600);
    }

    public static function jwtAlg(): string
    {
        return self::env('JWT_ALG') ?? (string) self::get('jwt.alg', 'HS256');
    }

    /**
     * Allowed CORS origins as an array, parsed from CORS_ALLOWED_ORIGINS env (comma-separated).
     */
    public static function corsAllowedOrigins(): array
    {
        $raw = self::env('CORS_ALLOWED_ORIGINS') ?? '';
        if ($raw === '') { return []; }
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_values(array_filter($parts, fn($v) => $v !== ''));
        return $parts;
    }

    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $file = dirname(__DIR__, 3) . '/config/parameters.php';
        self::$cache = file_exists($file) ? (require $file) : [];
        self::ensureEnvLoaded();
        return self::$cache;
    }

    private static function ensureEnvLoaded(): void
    {
        if (self::$envLoaded) { return; }
        $root = dirname(__DIR__, 3);
        $envFile = $root . '/.env';
        (self::$dotenv ??= new Dotenv())->load($envFile);
        self::$envLoaded = true;
    }
}
