<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class UserController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function list(Request $request, array $params): JsonResponse
    {
        // Require auth (Kernel already enforces for non-login routes)
        $repo = $this->em->getRepository(User::class);
        $limit = max(1, min(200, (int)($request->query->get('limit', 100))));
        $offset = max(0, (int)($request->query->get('offset', 0)));
        $q = trim((string)$request->query->get('q', ''));

        $qb = $repo->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        if ($q !== '') {
            $qb->where('LOWER(u.email) LIKE :q')->setParameter('q', '%' . strtolower($q) . '%');
        }
        $users = $qb->getQuery()->getResult();

        $items = array_map(function(User $u) {
            return [
                'id' => $u->getId(),
                'email' => $u->getEmail(),
            ];
        }, $users);

        return new JsonResponse([
            'items' => $items,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $roleName = (string)($claims['role'] ?? '');
        if ($roleName !== 'admin') { return new JsonResponse(['error' => 'forbidden'], 403); }

        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $roleReq = (string)($data['role'] ?? 'user');
        if ($email === '' || $password === '') {
            return new JsonResponse(['error' => 'email and password are required'], 400);
        }

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) { return new JsonResponse(['error' => 'email already exists'], 409); }

        $roleRepo = $this->em->getRepository(Role::class);
        $role = $roleRepo->findOneBy(['name' => $roleReq]);
        if (!$role) {
            $role = new Role($roleReq);
            $this->em->persist($role);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = new User($email, $hash, $role);
        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $role->getName(),
        ], 201);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $roleName = (string)($claims['role'] ?? '');
        if ($roleName !== 'admin') { return new JsonResponse(['error' => 'forbidden'], 403); }

        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($id <= 0) { return new JsonResponse(['error' => 'invalid id'], 400); }
        $user = $this->em->find(User::class, $id);
        if (!$user) { return new JsonResponse(['error' => 'not found'], 404); }

        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        if (array_key_exists('email', $data)) {
            $email = trim((string)$data['email']);
            if ($email === '') { return new JsonResponse(['error' => 'email cannot be empty'], 400); }
            $exists = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($exists && $exists->getId() !== $user->getId()) { return new JsonResponse(['error' => 'email already exists'], 409); }
            $user->setEmail($email);
        }
        if (array_key_exists('password', $data)) {
            $pwd = (string)$data['password'];
            if ($pwd === '') { return new JsonResponse(['error' => 'password cannot be empty'], 400); }
            $user->setPasswordHash(password_hash($pwd, PASSWORD_DEFAULT));
        }
        if (array_key_exists('role', $data)) {
            $roleReq = trim((string)$data['role']);
            if ($roleReq !== '') {
                $roleRepo = $this->em->getRepository(Role::class);
                $role = $roleRepo->findOneBy(['name' => $roleReq]);
                if (!$role) {
                    $role = new Role($roleReq);
                    $this->em->persist($role);
                }
                $user->setRole($role);
            }
        }

        $this->em->flush();
        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()->getName(),
        ]);
    }
}
