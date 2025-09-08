<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use App\Controller\HelloController;
use App\Controller\NoteController;
use App\Controller\FranchiseController;
use App\Controller\PackageController;
use App\Controller\AuthController;
use App\Controller\AdminLogController;
use App\Controller\AdminBanController;

$routes = new RouteCollection();

$routes->add('hello_name', new Route('/hello/{name}', [
    '_controller' => HelloController::class . '::hello'
]));

$routes->add('hello', new Route('/hello', [
    '_controller' => HelloController::class . '::hello'
]));

$routes->add('notes_list', new Route('/notes', [
    '_controller' => NoteController::class . '::list',
    '_method' => 'GET',
]));

$routes->add('notes_create', new Route('/notes/create', [
    '_controller' => NoteController::class . '::create',
    '_method' => 'POST',
]));

$routes->add('franchise_list', new Route('/franchises', [
    '_controller' => FranchiseController::class . '::list',
    '_method' => 'GET',
]));

$routes->add('franchise_create', new Route('/franchises/create', [
    '_controller' => FranchiseController::class . '::create',
    '_method' => 'POST',
]));

$routes->add('franchise_add_comment', new Route('/franchises/{id}/comments', [
    '_controller' => FranchiseController::class . '::addComment',
    '_method' => 'POST',
]));

$routes->add('franchise_add_link', new Route('/franchises/{id}/links', [
    '_controller' => FranchiseController::class . '::addLink',
    '_method' => 'POST',
]));

$routes->add('packages_list', new Route('/packages', [
    '_controller' => PackageController::class . '::list',
    '_method' => 'GET',
]));

$routes->add('packages_create', new Route('/packages/create', [
    '_controller' => PackageController::class . '::create',
    '_method' => 'POST',
]));

$routes->add('auth_login', new Route('/auth/login', [
    '_controller' => AuthController::class . '::login',
    '_method' => 'POST',
]));

$routes->add('admin_logs', new Route('/admin/logs', [
    '_controller' => AdminLogController::class . '::list',
    '_method' => 'GET',
]));

$routes->add('admin_ban_ip', new Route('/admin/ban-ip', [
    '_controller' => AdminBanController::class . '::ban',
    '_method' => 'POST',
]));

return $routes;
