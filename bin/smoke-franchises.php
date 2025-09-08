<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\FranchiseController;
use App\Infrastructure\DI\Container;
use Symfony\Component\HttpFoundation\Request;

$container = new Container();
$controller = $container->get(FranchiseController::class);

// Create
$createReq = Request::create('/franchises/create', 'GET', [], [], [], [], json_encode([
    'name' => 'Umbrella Corp',
    'code' => 'UMB01',
]));
$createRes = $controller->create($createReq, []);
echo 'CREATE: ' . $createRes->getStatusCode() . ' ' . $createRes->getContent() . PHP_EOL;

// List
$listReq = Request::create('/franchises', 'GET');
$listRes = $controller->list($listReq, []);
echo 'LIST: ' . $listRes->getStatusCode() . ' ' . $listRes->getContent() . PHP_EOL;
