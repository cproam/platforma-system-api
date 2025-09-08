<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Package;
use App\Entity\PackageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class PackageController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function list(Request $request, array $params): JsonResponse
    {
        $repo = $this->em->getRepository(Package::class);
        $items = $repo->findAll();
        return new JsonResponse(array_map(static function (Package $p): array {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'type' => $p->getType()->value,
                'leadCount' => $p->getLeadCount(),
            ];
        }, $items));
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $name = (string)($data['name'] ?? '');
        $type = (string)($data['type'] ?? '');
        $lead = (int)($data['leadCount'] ?? 0);

        if ($name === '' || $type === '' || $lead <= 0) {
            return new JsonResponse(['error' => 'name, type, leadCount are required (leadCount > 0)'], 400);
        }

        $typeEnum = match ($type) {
            PackageType::PAID->value => PackageType::PAID,
            PackageType::TEST_DRIVE->value => PackageType::TEST_DRIVE,
            default => null,
        };

        if ($typeEnum === null) {
            return new JsonResponse(['error' => 'Invalid type. Use paid or test-drive'], 400);
        }

        $package = new Package($name, $typeEnum, $lead);
        $this->em->persist($package);
        $this->em->flush();

        return new JsonResponse([
            'id' => $package->getId(),
            'name' => $package->getName(),
            'type' => $package->getType()->value,
            'leadCount' => $package->getLeadCount(),
        ], 201);
    }
}
