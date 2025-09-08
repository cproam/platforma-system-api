<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\DI\Container;
use App\Controller\AuthController;
use App\Controller\FranchiseController;
use Symfony\Component\HttpFoundation\Request;

$container = new Container();
$auth = $container->get(AuthController::class);
$fr = $container->get(FranchiseController::class);

// Seed admin may be needed in DB externally

// Login as admin
$loginRes = $auth->login(Request::create('/auth/login', 'POST', [], [], [], [], json_encode([
  'email' => 'admin@example.com',
  'password' => 'password'
])), []);
$tok = json_decode($loginRes->getContent(), true)['token'] ?? '';

echo 'LOGIN: ' . $loginRes->getStatusCode() . PHP_EOL;

// Create franchise
$createFr = $fr->create(Request::create('/franchises/create', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $tok], json_encode([
  'name' => 'With Comments', 'code' => 'WC01',
  'comments' => [ ['content' => 'First'] ]
])), []);
echo 'FR CREATE: ' . $createFr->getStatusCode() . ' ' . $createFr->getContent() . PHP_EOL;
$fid = json_decode($createFr->getContent(), true)['id'] ?? 0;

// Append comment
$addCom = $fr->addComment(Request::create("/franchises/$fid/comments", 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $tok], json_encode([
  'content' => 'Second'
])), ['id' => $fid]);

echo 'ADD COMMENT: ' . $addCom->getStatusCode() . ' ' . $addCom->getContent() . PHP_EOL;
