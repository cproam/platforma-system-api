<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
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
}
