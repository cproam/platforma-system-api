<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\PackageController;
use App\Infrastructure\DI\Container;
use Symfony\Component\HttpFoundation\Request;

$container = new Container();
$controller = $container->get(PackageController::class);

// Create
$createReq = Request::create('/packages/create', 'GET', [], [], [], [], json_encode([
    'name' => 'Starter Test',
    'type' => 'test-drive',
    'leadCount' => 10,
]));
$createRes = $controller->create($createReq, []);
echo 'CREATE: ' . $createRes->getStatusCode() . ' ' . $createRes->getContent() . PHP_EOL;

// List
$listReq = Request::create('/packages', 'GET');
$listRes = $controller->list($listReq, []);
echo 'LIST: ' . $listRes->getStatusCode() . ' ' . $listRes->getContent() . PHP_EOL;
