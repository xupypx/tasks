<?php
require_once __DIR__ . '/bootstrap.php';

use Core\Router;
use App\Controllers\HomeController;
use App\Controllers\TaskController;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\AdminController;
use App\Controllers\Api\TaskApiController;
use App\Middleware\Dispatcher;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Controllers\ProjectController;
use App\Controllers\SolutionController;


$projectController = new ProjectController();
$router = new Router();
$dispatcher = new Dispatcher();

// Применяем AuthMiddleware ко всем защищённым маршрутам
if (preg_match('#^/(tasks|dashboard|admin)#', $_SERVER['REQUEST_URI'])) {
    $dispatcher->add(AuthMiddleware::class);
}

// Применяем RoleMiddleware (как строку) к /admin маршрутам
if (preg_match('#^/admin#', $_SERVER['REQUEST_URI'])) {
    // Передача параметра 'admin' будет внутри самого middleware, через конструктор или статическую переменную
    // Поэтому здесь регистрируем класс как строку:
    $dispatcher->add(RoleMiddleware::class);
}

$dispatcher->handle(); // Запускаем middleware pipeline

// --------------------
// Роуты
// --------------------

// Главная и dashboard
$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'dashboard']);

// Tasks
$router->get('/tasks/create', [TaskController::class, 'create']);
$router->post('/tasks/store', [TaskController::class, 'store']);
$router->get('/tasks/list', [TaskController::class, 'list']);
$router->get('/tasks/my', [TaskController::class, 'myTasks']);
$router->get('/tasks/{id}', [TaskController::class, 'show']);
$router->get('/tasks/success/{id}', [TaskController::class, 'success']);
$router->get('/tasks/edit/{id}', [TaskController::class, 'edit']);
$router->post('/tasks/update/{id}', [TaskController::class, 'update']);
$router->post('/tasks/delete/{id}', [TaskController::class, 'delete']);


// Projects
$router->get('/projects', [$projectController, 'list']);
$router->get('/projects/create', [$projectController, 'createForm']);
$router->post('/projects/create', [$projectController, 'create']);
$router->get('/projects/show/{id}', [$projectController, 'show']);
$router->post('/projects/delete/{id}', [$projectController, 'delete']);
$router->get('/projects/edit/{id}', [ProjectController::class, 'edit']);
$router->post('/projects/update/{id}', [ProjectController::class, 'update']);


// Admin
$router->get('/admin/users', [AdminController::class, 'users']);
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/users/reassign/{id}', [AdminController::class, 'reassignForm']);
$router->post('/admin/users/reassign/{id}', [AdminController::class, 'reassign']);
$router->post('/admin/users/delete/{id}', [AdminController::class, 'deleteUser']);
$router->get('/admin/users/edit/{id}', [UserController::class, 'edit']);
$router->post('/admin/users/update/{id}', [UserController::class, 'update']);

// Settings
$router->get('/admin/settings', [App\Controllers\SettingsController::class, 'index']);
$router->post('/admin/settings/update', [App\Controllers\SettingsController::class, 'update']);
$router->post('/admin/settings/create', [App\Controllers\SettingsController::class, 'create']);
$router->post('/admin/settings/delete', [App\Controllers\SettingsController::class, 'delete']);

// Auth
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/register', [UserController::class, 'create']);
$router->post('/register', [UserController::class, 'store']);


// users
$router->get('/users/{id}', [UserController::class, 'show']);


// Password Reset
$router->get('/forgot-password', [UserController::class, 'sendPasswordReset']);
$router->post('/forgot-password', [UserController::class, 'sendPasswordReset']);
$router->get('/reset-password', [UserController::class, 'resetPassword']);
$router->post('/reset-password', [UserController::class, 'resetPassword']);


// Solutions
// $router->post('/solutions/store', [SolutionController::class, 'store']);
$router->post('/solutions/store/{task_id}', [SolutionController::class, 'store']);
$router->get('/solutions/edit/{id}', [SolutionController::class, 'edit']);
$router->post('/solutions/update/{id}', [SolutionController::class, 'update']);
$router->post('/solutions/delete/{id}', [SolutionController::class, 'delete']);


// API
// $router->get('/api/tasks', [TaskApiController::class, 'index']);
// $router->post('/api/tasks', [TaskApiController::class, 'store']);
// $router->get('/api/tasks/{id}', [TaskApiController::class, 'show']);
$router->get('/api/tasks', [App\Controllers\Api\TaskApiController::class, 'index']);
$router->get('/api/tasks/{id}', [App\Controllers\Api\TaskApiController::class, 'show']);
$router->post('/api/tasks', [App\Controllers\Api\TaskApiController::class, 'store']);
$router->put('/api/tasks/{id}', [App\Controllers\Api\TaskApiController::class, 'update']);
$router->delete('/api/tasks/{id}', [App\Controllers\Api\TaskApiController::class, 'delete']);


$router->dispatch($_SERVER['REQUEST_URI']);

