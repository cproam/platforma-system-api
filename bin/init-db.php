<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Doctrine\EntityManagerFactory;
use App\Entity\Note;
use App\Entity\Franchise;
use App\Entity\Package;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\LogEntry;
use App\Entity\Comment;
use App\Entity\Link;
use App\Entity\FranchiseStatus;
use App\Infrastructure\Config\Config;

$databasePath = Config::dbPath();
@mkdir(dirname($databasePath), 0777, true);

$em = EntityManagerFactory::sqlite($databasePath);
$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
$classes = [
    $em->getClassMetadata(Note::class),
    $em->getClassMetadata(Franchise::class),
    $em->getClassMetadata(Package::class),
    $em->getClassMetadata(Role::class),
    $em->getClassMetadata(User::class),
    $em->getClassMetadata(LogEntry::class),
    $em->getClassMetadata(Comment::class),
    $em->getClassMetadata(Link::class),
];
$schemaTool->dropSchema($classes);
$schemaTool->createSchema($classes);

echo "SQLite schema created at $databasePath\n";
