<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\DI\Container;
use App\Entity\Role;
use App\Entity\User;

$container = new Container();
/** @var Doctrine\ORM\EntityManagerInterface $em */
$em = $container->get(Doctrine\ORM\EntityManagerInterface::class);

$role = new Role('admin');
$em->persist($role);

$user = new User('admin@example.com', password_hash('password', PASSWORD_DEFAULT), $role);
$em->persist($user);
$em->flush();

echo "Seeded admin: admin@example.com / password\n";
