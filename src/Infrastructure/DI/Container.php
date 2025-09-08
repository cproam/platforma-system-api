<?php declare(strict_types=1);

namespace App\Infrastructure\DI;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Doctrine\EntityManagerFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\NoteController;
use App\Controller\FranchiseController;
use App\Controller\PackageController;
use App\Controller\AuthController;
use App\Infrastructure\Security\JwtService;
use App\Controller\AdminLogController;
use App\Controller\AdminBanController;

final class Container
{
    /** @var array<string, mixed> */
    private array $services = [];

    public function get(string $id): mixed
    {
        return $this->services[$id] ??= $this->create($id);
    }

    private function create(string $id): mixed
    {
        return match ($id) {
            EntityManagerInterface::class => EntityManagerFactory::sqlite(Config::dbPath()),
            NoteController::class => new NoteController($this->get(EntityManagerInterface::class)),
            FranchiseController::class => new FranchiseController($this->get(EntityManagerInterface::class)),
            PackageController::class => new PackageController($this->get(EntityManagerInterface::class)),
            JwtService::class => new JwtService(),
            AuthController::class => new AuthController(
                $this->get(EntityManagerInterface::class),
                $this->get(JwtService::class)
            ),
            AdminLogController::class => new AdminLogController(
                $this->get(EntityManagerInterface::class)
            ),
            AdminBanController::class => new AdminBanController(
                $this->get(EntityManagerInterface::class)
            ),
            default => class_exists($id)
                ? new $id()
                : throw new \InvalidArgumentException("Unknown service: $id"),
        };
    }
}
