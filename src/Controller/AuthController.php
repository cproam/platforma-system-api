<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Validation\Validator;
use Respect\Validation\Validator as v;

final class AuthController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JwtService $jwt,
        private readonly Validator $validator,
    ) {}

    public function login(Request $request, array $params): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $errors = $this->validator->validate($data, [
            'email' => v::stringType()->notEmpty()->email(),
            'password' => v::stringType()->notEmpty()->length(6, null),
        ]);

        if ($errors !== []) {
            return new JsonResponse(['errors' => $errors], 400);
        }
        $email = (string)($data['email']);
        $password = (string)($data['password']);

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
