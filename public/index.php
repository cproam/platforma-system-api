<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use App\Infrastructure\DI\Container;
use Symfony\Component\HttpFoundation\Request;

$routes = require __DIR__ . '/../config/routes.php';

$container = new Container();
$kernel = new Kernel($routes, $container);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
