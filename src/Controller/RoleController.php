<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class RoleController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function list(Request $request, array $params): JsonResponse
    {
        $limit = max(1, min(200, (int)($request->query->get('limit', 100))));
        $offset = max(0, (int)($request->query->get('offset', 0)));
        $q = trim((string)$request->query->get('q', ''));

        $qb = $this->em->getRepository(Role::class)->createQueryBuilder('r')
            ->orderBy('r.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        if ($q !== '') {
            $qb->where('LOWER(r.name) LIKE :q')->setParameter('q', '%' . strtolower($q) . '%');
        }
        $roles = $qb->getQuery()->getResult();

        $items = array_map(fn(Role $r) => [ 'id' => $r->getId(), 'name' => $r->getName() ], $roles);
        return new JsonResponse([ 'items' => $items, 'limit' => $limit, 'offset' => $offset ]);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $roleName = (string)($claims['role'] ?? '');
        if ($roleName !== 'admin') { return new JsonResponse(['error' => 'forbidden'], 403); }

        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') { return new JsonResponse(['error' => 'name is required'], 400); }

        $repo = $this->em->getRepository(Role::class);
        $existing = $repo->findOneBy(['name' => $name]);
        if ($existing) { return new JsonResponse(['error' => 'role already exists'], 409); }

        $role = new Role($name);
        $this->em->persist($role);
        $this->em->flush();

        return new JsonResponse([ 'id' => $role->getId(), 'name' => $role->getName() ], 201);
    }
}
