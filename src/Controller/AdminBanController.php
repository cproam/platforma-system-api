<?php declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Role;

final class AdminBanController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function ban(Request $request, array $params): JsonResponse
    {
        $claims = (array) $request->attributes->get('auth', []);
        $role = (string)($claims['role'] ?? '');
        if ($role !== 'admin') {
            return new JsonResponse(['error' => 'forbidden'], 403);
        }

        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $ip = (string)($data['ip'] ?? '');
        if ($ip === '') {
            return new JsonResponse(['error' => 'ip is required'], 400);
        }

        // Find or create an Anon user for this IP, then ban it
        $roleRepo = $this->em->getRepository(Role::class);
        $anonRole = $roleRepo->findOneBy(['name' => 'anon']);
        if (!$anonRole) {
            $anonRole = new Role('anon');
            $this->em->persist($anonRole);
        }

        $userRepo = $this->em->getRepository(User::class);
        /** @var User|null $anonUser */
        $anonUser = $userRepo->findOneBy(['ipAddress' => $ip, 'email' => $ip.'@anon.local']);
        if (!$anonUser) {
            $anonUser = new User($ip.'@anon.local', '', $anonRole, $ip, true);
            $this->em->persist($anonUser);
        } else {
            $anonUser->setBanned(true);
        }
        $this->em->flush();

        return new JsonResponse(['status' => 'banned', 'ip' => $ip]);
    }
}
