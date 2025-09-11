<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\DI\ContainerFactory;
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$container = ContainerFactory::create();
$routes = require __DIR__ . '/../config/routes.php';
$kernel = new Kernel($routes, $container);

// Login as admin
$loginReq = Request::create('/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@example.com',
    'password' => 'password',
]));
$loginRes = $kernel->handle($loginReq);
$body = json_decode($loginRes->getContent(), true) ?: [];
$token = $body['token'] ?? '';

// Access a route anonymously (no token) to create anon user for IP
$notesReqNoAuth = Request::create('/notes', 'GET', [], [], [], [
    'REMOTE_ADDR' => '203.0.113.10'
]);
$notesResNoAuth = $kernel->handle($notesReqNoAuth);
echo 'ANON BEFORE BAN: ' . $notesResNoAuth->getStatusCode() . ' ' . $notesResNoAuth->getContent() . PHP_EOL;

// Ban that IP
$banReq = Request::create('/admin/ban-ip', 'POST', [], [], [], [
    'HTTP_Authorization' => 'Bearer ' . $token,
], json_encode(['ip' => '203.0.113.10']));
$banRes = $kernel->handle($banReq);
echo 'BAN: ' . $banRes->getStatusCode() . ' ' . $banRes->getContent() . PHP_EOL;

// Try again anonymously from same IP
$notesReqNoAuth2 = Request::create('/notes', 'GET', [], [], [], [
    'REMOTE_ADDR' => '203.0.113.10'
]);
$notesResNoAuth2 = $kernel->handle($notesReqNoAuth2);
echo 'ANON AFTER BAN: ' . $notesResNoAuth2->getStatusCode() . ' ' . $notesResNoAuth2->getContent() . PHP_EOL;
