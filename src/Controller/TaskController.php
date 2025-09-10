<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Franchise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class TaskController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function create(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $creatorId = isset($claims['sub']) ? (int)$claims['sub'] : 0;
        if ($creatorId <= 0) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }
        $creator = $this->em->find(User::class, $creatorId);
        if (!$creator) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $assignedToId = (int)($data['assignedToId'] ?? 0);
        $description = trim((string)($data['description'] ?? ''));
        $deadlineRaw = (string)($data['deadline'] ?? '');
        $franchiseId = isset($data['franchiseId']) ? (int)$data['franchiseId'] : null;

        if ($assignedToId <= 0 || $description === '' || $deadlineRaw === '') {
            return new JsonResponse(['error' => 'assignedToId, description, deadline are required'], 400);
        }
        try {
            $deadline = new \DateTimeImmutable($deadlineRaw);
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'invalid deadline'], 400);
        }

        $assignee = $this->em->find(User::class, $assignedToId);
        if (!$assignee) {
            return new JsonResponse(['error' => 'assignee not found'], 400);
        }

        $franchise = null;
        if ($franchiseId) {
            $franchise = $this->em->find(Franchise::class, $franchiseId);
            if (!$franchise) {
                return new JsonResponse(['error' => 'franchise not found'], 400);
            }
        }

        $task = new Task($creator, $assignee, $description, $deadline, $franchise);
        $this->em->persist($task);
        $this->em->flush();

        return new JsonResponse($this->serialize($task), 201);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $uid = isset($claims['sub']) ? (int)$claims['sub'] : 0;
        if ($uid <= 0) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($id <= 0) {
            return new JsonResponse(['error' => 'invalid id'], 400);
        }

        $task = $this->em->find(Task::class, $id);
        if (!$task) {
            return new JsonResponse(['error' => 'not found'], 404);
        }

        $data = json_decode($request->getContent() ?: '[]', true) ?: [];

        if (array_key_exists('description', $data)) {
            $desc = trim((string)$data['description']);
            if ($desc === '') {
                return new JsonResponse(['error' => 'description cannot be empty'], 400);
            }
            $task->setDescription($desc);
        }
        if (array_key_exists('deadline', $data)) {
            try {
                $task->setDeadline(new \DateTimeImmutable((string)$data['deadline']));
            } catch (\Throwable) {
                return new JsonResponse(['error' => 'invalid deadline'], 400);
            }
        }
        if (array_key_exists('assignedToId', $data)) {
            $aid = (int)$data['assignedToId'];
            if ($aid <= 0) {
                return new JsonResponse(['error' => 'invalid assignedToId'], 400);
            }
            $assignee = $this->em->find(User::class, $aid);
            if (!$assignee) {
                return new JsonResponse(['error' => 'assignee not found'], 400);
            }
            $task->setAssignedTo($assignee);
        }
        if (array_key_exists('franchiseId', $data)) {
            $fid = $data['franchiseId'] !== null ? (int)$data['franchiseId'] : null;
            $fr = null;
            if ($fid) {
                $fr = $this->em->find(Franchise::class, $fid);
                if (!$fr) {
                    return new JsonResponse(['error' => 'franchise not found'], 400);
                }
            }
            $task->setFranchise($fr);
        }
        if (array_key_exists('seen', $data)) {
            $task->setSeen((bool)$data['seen']);
        }

        $this->em->flush();
        return new JsonResponse($this->serialize($task));
    }

    public function view(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $uid = isset($claims['sub']) ? (int)$claims['sub'] : 0;
        if ($uid <= 0) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($id <= 0) {
            return new JsonResponse(['error' => 'invalid id'], 400);
        }

        $task = $this->em->find(Task::class, $id);
        if (!$task) {
            return new JsonResponse(['error' => 'not found'], 404);
        }

        return new JsonResponse($this->serialize($task));
    }

    public function myTasks(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $uid = isset($claims['sub']) ? (int)$claims['sub'] : 0;
        if ($uid <= 0) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        $repo = $this->em->getRepository(Task::class);
        $tasks = $repo->findBy(['assignedTo' => $uid], ['deadline' => 'ASC', 'id' => 'DESC']);
        return new JsonResponse(array_map([$this, 'serialize'], $tasks));
    }

    public function unread(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $uid = isset($claims['sub']) ? (int)$claims['sub'] : 0;
        if ($uid <= 0) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        $sinceId = (int)($request->query->get('sinceId', 0));
        $timeout = (int)($request->query->get('timeout', 25));
        $limit = (int)($request->query->get('limit', 50));
        if ($timeout < 1) {
            $timeout = 1;
        }
        if ($timeout > 60) {
            $timeout = 60;
        }
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $deadlineTs = microtime(true) + $timeout;
        $pollIntervalUs = 500000; // 0.5s

        do {
            $qb = $this->em->createQueryBuilder();
            $qb->select('t')
                ->from(Task::class, 't')
                ->where('IDENTITY(t.assignedTo) = :uid')
                ->andWhere('t.seen = false')
                ->setParameter('uid', $uid)
                ->orderBy('t.id', 'ASC')
                ->setMaxResults($limit);
            if ($sinceId > 0) {
                $qb->andWhere('t.id > :sid')->setParameter('sid', $sinceId);
            }

            $tasks = $qb->getQuery()->getResult();
            if (!empty($tasks)) {
                $nextSinceId = $sinceId;
                foreach ($tasks as $task) {
                    $id = $task->getId() ?? 0;
                    if ($id > $nextSinceId) {
                        $nextSinceId = $id;
                    }
                }
                return new JsonResponse([
                    'items' => array_map([$this, 'serialize'], $tasks),
                    'nextSinceId' => $nextSinceId,
                ], 200);
            }

            usleep($pollIntervalUs);
        } while (microtime(true) < $deadlineTs);

        return new JsonResponse([
            'items' => [],
            'nextSinceId' => $sinceId,
        ], 200);
    }

    private function serialize(Task $t): array
    {
        return [
            'id' => $t->getId(),
            'description' => $t->getDescription(),
            'deadline' => $t->getDeadline()->format(DATE_ATOM),
            'createdAt' => $t->getCreatedAt()->format(DATE_ATOM),
            'seen' => $t->isSeen(),
            'createdBy' => [
                'id' => $t->getCreatedBy()->getId(),
                'email' => $t->getCreatedBy()->getEmail(),
            ],
            'assignedTo' => [
                'id' => $t->getAssignedTo()->getId(),
                'email' => $t->getAssignedTo()->getEmail(),
            ],
            'franchise' => $t->getFranchise() ? [
                'id' => $t->getFranchise()->getId(),
                'name' => $t->getFranchise()->getName(),
                'code' => $t->getFranchise()->getCode(),
            ] : null,
        ];
    }
}
