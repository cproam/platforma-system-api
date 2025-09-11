<?php declare(strict_types=1);

namespace App\Infrastructure\DI;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Doctrine\EntityManagerFactory;
use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

final class ContainerFactory
{
    public static function create(): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(true);
        $builder->addDefinitions([
            EntityManagerInterface::class => fn() => EntityManagerFactory::sqlite(Config::dbPath()),
        ]);
        return $builder->build();
    }
}
