<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\FranchiseController;
use App\Infrastructure\DI\Container;
use Symfony\Component\HttpFoundation\Request;

$container = new Container();
$controller = $container->get(FranchiseController::class);

// Create a franchise
$createReq = Request::create('/franchises/create', 'POST', [], [], [], [], json_encode([
    'name' => 'Stark Industries',
    'code' => 'STK01',
]));
$createRes = $controller->create($createReq, []);
echo 'CREATE: ' . $createRes->getStatusCode() . ' ' . $createRes->getContent() . PHP_EOL;
$data = json_decode($createRes->getContent(), true);
$id = $data['id'] ?? 0;

// Append a comment
$commentReq = Request::create("/franchises/$id/comments", 'POST', [], [], [], [], json_encode([
    'content' => 'Looks promising!'
]));
$commentRes = $controller->addComment($commentReq, ['id' => $id]);
echo 'ADD COMMENT: ' . $commentRes->getStatusCode() . ' ' . $commentRes->getContent() . PHP_EOL;

// Append a link
$linkReq = Request::create("/franchises/$id/links", 'POST', [], [], [], [], json_encode([
    'url' => 'https://stark.example',
    'label' => 'Homepage'
]));
$linkRes = $controller->addLink($linkReq, ['id' => $id]);
echo 'ADD LINK: ' . $linkRes->getStatusCode() . ' ' . $linkRes->getContent() . PHP_EOL;

// List with filters, pagination and q
$listReq = Request::create('/franchises', 'GET', ['limit' => 1, 'offset' => 0, 'q' => 'stark']);
$listRes = $controller->list($listReq, []);
echo 'LIST: ' . $listRes->getStatusCode() . ' ' . $listRes->getContent() . PHP_EOL;
