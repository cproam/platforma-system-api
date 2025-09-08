<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\NoteController;
use App\Infrastructure\DI\Container;
use Symfony\Component\HttpFoundation\Request;

$container = new Container();
$controller = $container->get(NoteController::class);

// Create a note
$reqCreate = Request::create('/notes/create', 'GET', [], [], [], [], json_encode([
    'title' => 'First',
    'content' => 'Hello ORM',
]));
$resCreate = $controller->create($reqCreate, []);
echo 'CREATE: ' . $resCreate->getStatusCode() . ' ' . $resCreate->getContent() . PHP_EOL;

// List notes
$reqList = Request::create('/notes', 'GET');
$resList = $controller->list($reqList, []);
echo 'LIST: ' . $resList->getStatusCode() . ' ' . $resList->getContent() . PHP_EOL;
