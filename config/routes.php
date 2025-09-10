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
use App\Controller\TaskController;
use App\Controller\UserController;
use App\Controller\RoleController;

$routes = new RouteCollection();

$routes->add('hello_name', new Route('/hello/{name}', [
    '_controller' => HelloController::class . '::hello'
]));

$routes->add('hello', new Route('/hello', [
    '_controller' => HelloController::class . '::hello'
]));

$routes->add('notes_list', new Route('/notes', [
    '_controller' => NoteController::class . '::list',
], [], [], '', [], ['GET']));

$routes->add('notes_create', new Route('/notes/create', [
    '_controller' => NoteController::class . '::create',
], [], [], '', [], ['POST']));

$routes->add('franchise_list', new Route('/franchises', [
    '_controller' => FranchiseController::class . '::list',
], [], [], '', [], ['GET']));

$routes->add('franchise_create', new Route('/franchises/create', [
    '_controller' => FranchiseController::class . '::create',
], [], [], '', [], ['POST']));

$routes->add('franchise_update', new Route('/franchises/{id}', [
    '_controller' => FranchiseController::class . '::update',
], [], [], '', [], ['PUT']));

$routes->add('franchise_view', new Route('/franchises/{id}', [
    '_controller' => FranchiseController::class . '::view',
], [], [], '', [], ['GET']));

$routes->add('franchise_add_comment', new Route('/franchises/{id}/comments', [
    '_controller' => FranchiseController::class . '::addComment',
], [], [], '', [], ['POST']));

$routes->add('franchise_add_link', new Route('/franchises/{id}/links', [
    '_controller' => FranchiseController::class . '::addLink',
], [], [], '', [], ['POST']));

$routes->add('packages_list', new Route('/packages', [
    '_controller' => PackageController::class . '::list',
], [], [], '', [], ['GET']));

$routes->add('packages_create', new Route('/packages/create', [
    '_controller' => PackageController::class . '::create',
], [], [], '', [], ['POST']));

$routes->add('auth_login', new Route('/auth/login', [
    '_controller' => AuthController::class . '::login',
], [], [], '', [], ['POST']));

$routes->add('admin_logs', new Route('/admin/logs', [
    '_controller' => AdminLogController::class . '::list',
], [], [], '', [], ['GET']));

$routes->add('admin_ban_ip', new Route('/admin/ban-ip', [
    '_controller' => AdminBanController::class . '::ban',
], [], [], '', [], ['POST']));

$routes->add('tasks_create', new Route('/tasks', [
    '_controller' => TaskController::class . '::create',
], [], [], '', [], ['POST']));

$routes->add('tasks_my', new Route('/tasks/my', [
    '_controller' => TaskController::class . '::myTasks',
], [], [], '', [], ['GET']));

$routes->add('tasks_unread', new Route('/tasks/unread', [
    '_controller' => TaskController::class . '::unread',
], [], [], '', [], ['GET']));

$routes->add('tasks_update', new Route('/tasks/{id}', [
    '_controller' => TaskController::class . '::update',
], ['id' => '\\d+'], [], '', [], ['PUT']));

$routes->add('tasks_view', new Route('/tasks/{id}', [
    '_controller' => TaskController::class . '::view',
], ['id' => '\\d+'], [], '', [], ['GET']));

$routes->add('users_list', new Route('/users', [
    '_controller' => UserController::class . '::list',
], [], [], '', [], ['GET']));

$routes->add('users_create', new Route('/users', [
    '_controller' => UserController::class . '::create',
], [], [], '', [], ['POST']));

$routes->add('users_update', new Route('/users/{id}', [
    '_controller' => UserController::class . '::update',
], [], [], '', [], ['PUT']));

$routes->add('roles_list', new Route('/roles', [
    '_controller' => RoleController::class . '::list',
], [], [], '', [], ['GET']));

$routes->add('roles_create', new Route('/roles', [
    '_controller' => RoleController::class . '::create',
], [], [], '', [], ['POST']));

return $routes;
