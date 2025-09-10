<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\LogEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AdminLogController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function list(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $role = (string)($claims['role'] ?? '');
        if ($role !== 'admin') {
            return new JsonResponse(['error' => 'forbidden'], 403);
        }

        $limit = max(1, min(200, (int)($request->query->get('limit', 50))));
        $offset = max(0, (int)($request->query->get('offset', 0)));

        $repo = $this->em->getRepository(LogEntry::class);
        $logs = $repo->findBy([], ['id' => 'DESC'], $limit, $offset);
    $items = array_map(static function (LogEntry $l): array {
            return [
                'id' => $l->getId(),
                'createdAt' => $l->getCreatedAt()->format(DATE_ATOM),
                'method' => $l->getMethod(),
                'path' => $l->getPath(),
                'status' => $l->getStatus(),
                'message' => $l->getMessage(),
        'userId' => $l->getUserId(),
        'action' => $l->getAction(),
        'object' => $l->getObject(),
            ];
        }, $logs);
        return new JsonResponse([
            'items' => $items,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }
}
