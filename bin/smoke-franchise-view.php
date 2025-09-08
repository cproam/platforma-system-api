<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\DI\Container;
use App\Controller\FranchiseController;
use Symfony\Component\HttpFoundation\Request;

$container = new Container();
$controller = $container->get(FranchiseController::class);

// Create a franchise
$createRes = $controller->create(Request::create('/franchises/create', 'POST', [], [], [], [], json_encode([
  'name' => 'View Me',
  'code' => 'VIEW01',
  'status' => 'published',
  'links' => [ ['url' => 'https://example.org', 'label' => 'Example'] ],
  'comments' => [ ['content' => 'hello'] ]
])), []);

echo 'CREATE: ' . $createRes->getStatusCode() . ' ' . $createRes->getContent() . PHP_EOL;
$fr = json_decode($createRes->getContent(), true);
$id = $fr['id'] ?? 0;

// View
$viewRes = $controller->view(Request::create("/franchise/$id", 'GET'), ['id' => $id]);
echo 'VIEW: ' . $viewRes->getStatusCode() . ' ' . $viewRes->getContent() . PHP_EOL;
