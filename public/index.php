<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use App\Infrastructure\DI\ContainerFactory;
use Symfony\Component\HttpFoundation\Request;

$routes = require __DIR__ . '/../config/routes.php';

$container = ContainerFactory::create();
$kernel = new Kernel($routes, $container);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
