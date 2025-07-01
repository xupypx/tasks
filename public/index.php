<?php
require_once __DIR__ . '/../bootstrap.php';

use core\Router;
use App\Controllers\HomeController;
use App\Controllers\TaskController;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\Api\TaskApiController;

$router = new Router();

// Web routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'dashboard']);
$router->get('/tasks/create', [TaskController::class, 'create']);
$router->post('/tasks/store', [TaskController::class, 'store']);
// Проверяем, как настроен роутинг для списка задач
$router->get('/tasks/list', [TaskController::class, 'list']);
$router->get('/tasks/{id}', [TaskController::class, 'show']);
$router->get('/tasks/success/{id}', [TaskController::class, 'success']); // Оставляем только этот маршрут
$router->post('/tasks/delete/{id}', [TaskController::class, 'delete']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/register', [UserController::class, 'create']);
$router->post('/register', [UserController::class, 'store']);

// Роуты для редактирования задачи
$router->get('/tasks/edit/{id}', [TaskController::class, 'edit']);
$router->post('/tasks/update/{id}', [TaskController::class, 'update']);
$router->post('/tasks/delete/{id}', [TaskController::class, 'delete']);

// Восстановление пароля
$router->get('/forgot-password', [UserController::class, 'sendPasswordReset']);
$router->post('/forgot-password', [UserController::class, 'sendPasswordReset']);
$router->get('/reset-password', [UserController::class, 'resetPassword']);
$router->post('/reset-password', [UserController::class, 'resetPassword']);


// ✅ API routes
$router->get('/api/tasks', [TaskApiController::class, 'index']);
$router->post('/api/tasks', [TaskApiController::class, 'store']);
$router->get('/api/tasks/{id}', [TaskApiController::class, 'show']);

$router->dispatch($_SERVER['REQUEST_URI']);
