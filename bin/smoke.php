<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$routes = require __DIR__ . '/../config/routes.php';
$kernel = new Kernel($routes);

$tests = [
    '/hello' => 'world',
    '/hello/Ada' => 'Ada',
];

foreach ($tests as $path => $expected) {
    $request = Request::create($path);
    $response = $kernel->handle($request);
    $data = json_decode($response->getContent(), true);
    $ok = isset($data['message']) && $data['message'] === "Hello, $expected!";
    echo ($ok ? 'PASS' : 'FAIL') . " $path => " . $response->getContent() . PHP_EOL;
}
