<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\DI\Container;
use App\Controller\FranchiseController;
use App\Controller\PackageController;
use Symfony\Component\HttpFoundation\Request;

$container = new Container();
$frController = $container->get(FranchiseController::class);
$pkgController = $container->get(PackageController::class);

// Create a franchise to tie to
$frRes = $frController->create(Request::create('/franchises/create', 'POST', [], [], [], [], json_encode([
  'name' => 'Tie Franchise', 'code' => 'TIE01'
])), []);
$fr = json_decode($frRes->getContent(), true);
$fid = $fr['id'] ?? 0;

echo 'FR CREATE: ' . $frRes->getStatusCode() . ' ' . $frRes->getContent() . PHP_EOL;

// Create package with franchiseId
$pkgRes = $pkgController->create(Request::create('/packages/create', 'POST', [], [], [], [], json_encode([
  'name' => 'Gold', 'type' => 'paid', 'leadCount' => 50, 'franchiseId' => $fid
])), []);

echo 'PKG CREATE: ' . $pkgRes->getStatusCode() . ' ' . $pkgRes->getContent() . PHP_EOL;

// List packages
$listRes = $pkgController->list(Request::create('/packages', 'GET'), []);
echo 'PKG LIST: ' . $listRes->getStatusCode() . ' ' . $listRes->getContent() . PHP_EOL;
