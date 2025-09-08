<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Infrastructure\Security\JwtService;

final class AuthController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JwtService $jwt,
    ) {}

    public function login(Request $request, array $params): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $email = (string)($data['email'] ?? '');
        $password = (string)($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return new JsonResponse(['error' => 'email and password are required'], 400);
        }

        $repo = $this->em->getRepository(User::class);
        /** @var User|null $user */
        $user = $repo->findOneBy(['email' => $email]);
        if (!$user || !password_verify($password, $user->getPasswordHash())) {
            return new JsonResponse(['error' => 'invalid credentials'], 401);
        }

        $token = $this->jwt->issueToken($user->getId() ?? 0, $user->getRole()->getName());
        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()->getName(),
            ],
        ]);
    }
}
