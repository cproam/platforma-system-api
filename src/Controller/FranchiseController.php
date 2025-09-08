<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Franchise;
use App\Entity\FranchiseStatus;
use App\Entity\Link;
use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class FranchiseController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {}

    public function list(Request $request, array $params): JsonResponse
    {
        $limit = max(1, min(200, (int)$request->query->get('limit', 50)));
        $offset = max(0, (int)$request->query->get('offset', 0));
        $statusParam = $request->query->get('status');
        $q = (string)$request->query->get('q', '');

        $qb = $this->em->createQueryBuilder()
            ->select('f')
            ->from(Franchise::class, 'f')
            ->orderBy('f.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($statusParam !== null && $statusParam !== '') {
            $s = (string)$statusParam;
            $status = match ($s) {
                FranchiseStatus::PUBLISHED->value => FranchiseStatus::PUBLISHED,
                FranchiseStatus::TESTING->value => FranchiseStatus::TESTING,
                FranchiseStatus::UNPUBLISHED->value => FranchiseStatus::UNPUBLISHED,
                default => null,
            };
            if ($status === null) {
                return new JsonResponse(['error' => 'invalid status'], 400);
            }
            $qb->andWhere('f.status = :status')->setParameter('status', $status);
        }

        if ($q !== '') {
            $like = '%' . strtolower($q) . '%';
            $qb->andWhere('LOWER(f.name) LIKE :q OR LOWER(f.code) LIKE :q')
               ->setParameter('q', $like);
        }

        $items = $qb->getQuery()->getResult();
        $serialized = array_map(static function (Franchise $f): array {
            return [
                'id' => $f->getId(),
                'name' => $f->getName(),
                'code' => $f->getCode(),
                'status' => $f->getStatus()->value,
                'email' => $f->getEmail(),
                'webhookUrl' => $f->getWebhookUrl(),
                'telegramId' => $f->getTelegramId(),
                'description' => $f->getDescription(),
                'cost' => $f->getCost(),
                'investment' => $f->getInvestment(),
                'paybackPeriod' => $f->getPaybackPeriod(),
                'monthlyIncome' => $f->getMonthlyIncome(),
                'publishedDurationDays' => $f->getPublishedDurationDays(),
                'createdAt' => $f->getCreatedAt()->format(DATE_ATOM),
                'links' => array_map(static fn(Link $l) => [
                    'id' => $l->getId(),
                    'url' => $l->getUrl(),
                    'label' => $l->getLabel(),
                ], $f->getLinks()->toArray()),
                'comments' => array_map(static fn(Comment $c) => [
                    'id' => $c->getId(),
                    'content' => $c->getContent(),
                    'createdAt' => $c->getCreatedAt()->format(DATE_ATOM),
                    'user' => $c->getUser() ? [
                        'id' => $c->getUser()->getId(),
                        'email' => $c->getUser()->getEmail(),
                    ] : null,
                ], $f->getComments()->toArray()),
            ];
        }, $items);

        return new JsonResponse([
            'items' => $serialized,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $name = (string)($data['name'] ?? '');
        $code = (string)($data['code'] ?? '');

        if ($name === '' || $code === '') {
            return new JsonResponse(['error' => 'name and code are required'], 400);
        }

        $franchise = new Franchise($name, $code);
        if (isset($data['status'])) {
            $s = (string)$data['status'];
            $status = match ($s) {
                FranchiseStatus::PUBLISHED->value => FranchiseStatus::PUBLISHED,
                FranchiseStatus::TESTING->value => FranchiseStatus::TESTING,
                FranchiseStatus::UNPUBLISHED->value => FranchiseStatus::UNPUBLISHED,
                default => null,
            };
            if ($status === null) {
                return new JsonResponse(['error' => 'invalid status'], 400);
            }
            $franchise->setStatus($status);
        }
        $franchise->setEmail($data['email'] ?? null);
        $franchise->setWebhookUrl($data['webhookUrl'] ?? null);
        $franchise->setTelegramId($data['telegramId'] ?? null);
        $franchise->setDescription($data['description'] ?? null);
        $franchise->setCost(isset($data['cost']) ? (float)$data['cost'] : null);
        $franchise->setInvestment(isset($data['investment']) ? (float)$data['investment'] : null);
        $franchise->setPaybackPeriod(isset($data['paybackPeriod']) ? (float)$data['paybackPeriod'] : null);
        $franchise->setMonthlyIncome(isset($data['monthlyIncome']) ? (float)$data['monthlyIncome'] : null);

        if (!empty($data['links']) && is_array($data['links'])) {
            foreach ($data['links'] as $lnk) {
                $url = (string)($lnk['url'] ?? '');
                if ($url === '') { continue; }
                $label = isset($lnk['label']) ? (string)$lnk['label'] : null;
                $franchise->addLink(new Link($franchise, $url, $label));
            }
        }

        if (!empty($data['comments']) && is_array($data['comments'])) {
            $user = $this->resolveUser($request);
            foreach ($data['comments'] as $com) {
                $content = trim((string)($com['content'] ?? ''));
                if ($content === '') { continue; }
                $comment = new Comment($franchise, $content);
                if ($user) { $comment->setUser($user); }
                $franchise->addComment($comment);
            }
        }
        $this->em->persist($franchise);
        $this->em->flush();

        return new JsonResponse([
            'id' => $franchise->getId(),
            'name' => $franchise->getName(),
            'code' => $franchise->getCode(),
            'status' => $franchise->getStatus()->value,
            'email' => $franchise->getEmail(),
            'webhookUrl' => $franchise->getWebhookUrl(),
            'telegramId' => $franchise->getTelegramId(),
            'description' => $franchise->getDescription(),
            'cost' => $franchise->getCost(),
            'investment' => $franchise->getInvestment(),
            'paybackPeriod' => $franchise->getPaybackPeriod(),
            'monthlyIncome' => $franchise->getMonthlyIncome(),
            'publishedDurationDays' => $franchise->getPublishedDurationDays(),
            'createdAt' => $franchise->getCreatedAt()->format(DATE_ATOM),
            'links' => array_map(static fn(Link $l) => [
                'id' => $l->getId(),
                'url' => $l->getUrl(),
                'label' => $l->getLabel(),
            ], $franchise->getLinks()->toArray()),
            'comments' => array_map(static fn(Comment $c) => [
                'id' => $c->getId(),
                'content' => $c->getContent(),
                'createdAt' => $c->getCreatedAt()->format(DATE_ATOM),
                'user' => $c->getUser() ? [
                    'id' => $c->getUser()->getId(),
                    'email' => $c->getUser()->getEmail(),
                ] : null,
            ], $franchise->getComments()->toArray()),
        ], 201);
    }

    public function view(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return new JsonResponse(['error' => 'invalid id'], 400);
        }
        $franchise = $this->em->find(Franchise::class, $id);
        if (!$franchise) {
            return new JsonResponse(['error' => 'not found'], 404);
        }
        return new JsonResponse([
            'id' => $franchise->getId(),
            'name' => $franchise->getName(),
            'code' => $franchise->getCode(),
            'status' => $franchise->getStatus()->value,
            'email' => $franchise->getEmail(),
            'webhookUrl' => $franchise->getWebhookUrl(),
            'telegramId' => $franchise->getTelegramId(),
            'description' => $franchise->getDescription(),
            'cost' => $franchise->getCost(),
            'investment' => $franchise->getInvestment(),
            'paybackPeriod' => $franchise->getPaybackPeriod(),
            'monthlyIncome' => $franchise->getMonthlyIncome(),
            'publishedDurationDays' => $franchise->getPublishedDurationDays(),
            'createdAt' => $franchise->getCreatedAt()->format(DATE_ATOM),
            'links' => array_map(static fn(Link $l) => [
                'id' => $l->getId(),
                'url' => $l->getUrl(),
                'label' => $l->getLabel(),
            ], $franchise->getLinks()->toArray()),
            'comments' => array_map(static fn(Comment $c) => [
                'id' => $c->getId(),
                'content' => $c->getContent(),
                'createdAt' => $c->getCreatedAt()->format(DATE_ATOM),
                'user' => $c->getUser() ? [
                    'id' => $c->getUser()->getId(),
                    'email' => $c->getUser()->getEmail(),
                ] : null,
            ], $franchise->getComments()->toArray()),
        ]);
    }

    public function addComment(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return new JsonResponse(['error' => 'invalid id'], 400);
        }
        $franchise = $this->em->find(Franchise::class, $id);
        if (!$franchise) {
            return new JsonResponse(['error' => 'not found'], 404);
        }
        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $content = trim((string)($data['content'] ?? ''));
        if ($content === '') {
            return new JsonResponse(['error' => 'content is required'], 400);
        }
    $user = $this->resolveUser($request);
    $comment = new Comment($franchise, $content);
    if ($user) { $comment->setUser($user); }
        $franchise->addComment($comment);
        $this->em->persist($comment);
        $this->em->flush();
        return new JsonResponse([
            'id' => $comment->getId(),
            'franchiseId' => $franchise->getId(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'user' => $comment->getUser() ? [
                'id' => $comment->getUser()->getId(),
                'email' => $comment->getUser()->getEmail(),
            ] : null,
        ], 201);
    }

    public function addLink(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return new JsonResponse(['error' => 'invalid id'], 400);
        }
        $franchise = $this->em->find(Franchise::class, $id);
        if (!$franchise) {
            return new JsonResponse(['error' => 'not found'], 404);
        }
        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $url = trim((string)($data['url'] ?? ''));
        $label = isset($data['label']) ? (string)$data['label'] : null;
        if ($url === '') {
            return new JsonResponse(['error' => 'url is required'], 400);
        }
        $link = new Link($franchise, $url, $label);
        $franchise->addLink($link);
        $this->em->persist($link);
        $this->em->flush();
        return new JsonResponse([
            'id' => $link->getId(),
            'franchiseId' => $franchise->getId(),
            'url' => $link->getUrl(),
            'label' => $link->getLabel(),
        ], 201);
    }

    private function resolveUser(Request $request): ?User
    {
        $claims = (array) $request->attributes->get('auth', []);
        $uid = isset($claims['sub']) ? (int)$claims['sub'] : 0;
        if ($uid > 0) {
            $u = $this->em->find(User::class, $uid);
            if ($u) { return $u; }
        }
        // Fallback for direct controller calls (e.g., smoke scripts): a stable anon user
        $role = $this->em->getRepository(Role::class)->findOneBy(['name' => 'anon']);
        if (!$role) {
            $role = new Role('anon');
            $this->em->persist($role);
            $this->em->flush();
        }
        $email = 'anon-controller@local';
        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User($email, '', $role);
            $this->em->persist($user);
            $this->em->flush();
        }
        return $user;
    }
}
