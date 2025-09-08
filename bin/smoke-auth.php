<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\DI\Container;
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$container = new Container();
$routes = require __DIR__ . '/../config/routes.php';
$kernel = new Kernel($routes, $container);

// Login
$loginReq = Request::create('/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@example.com',
    'password' => 'password',
]));
$loginRes = $kernel->handle($loginReq);
echo 'LOGIN: ' . $loginRes->getStatusCode() . ' ' . $loginRes->getContent() . PHP_EOL;

// Call protected route without token
$notesReqNoAuth = Request::create('/notes', 'GET');
$notesResNoAuth = $kernel->handle($notesReqNoAuth);
echo 'NOAUTH NOTES: ' . $notesResNoAuth->getStatusCode() . ' ' . $notesResNoAuth->getContent() . PHP_EOL;

// Call protected route with token
$body = json_decode($loginRes->getContent(), true) ?: [];
$token = $body['token'] ?? '';
$notesReqAuth = Request::create('/notes', 'GET', [], [], [], [
    'HTTP_Authorization' => 'Bearer ' . $token,
]);
$notesResAuth = $kernel->handle($notesReqAuth);
echo 'AUTH NOTES: ' . $notesResAuth->getStatusCode() . ' ' . $notesResAuth->getContent() . PHP_EOL;
