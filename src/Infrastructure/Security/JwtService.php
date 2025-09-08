<?php declare(strict_types=1);

namespace App\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Infrastructure\Config\Config;

final class JwtService
{
    public function issueToken(int $userId, string $role): string
    {
        $now = time();
    $ttl = Config::jwtTtl();
        $payload = [
            'sub' => $userId,
            'role' => $role,
            'iat' => $now,
            'exp' => $now + $ttl,
        ];
    $secret = Config::jwtSecret();
    $alg = Config::jwtAlg();
        return JWT::encode($payload, $secret, $alg);
    }

    public function verify(string $token): array
    {
    $secret = Config::jwtSecret();
    $alg = Config::jwtAlg();
        $decoded = JWT::decode($token, new Key($secret, $alg));
        /** @var array $arr */
        $arr = json_decode(json_encode($decoded), true) ?: [];
        return $arr;
    }
}
