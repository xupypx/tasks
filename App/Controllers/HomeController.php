<?php
namespace App\Controllers;

use App\Models\Task;
use core\View;
use function current_user;
use function require_login;

class HomeController {
    public function dashboard(): void {
        require_login();

        $user = current_user();
        $taskModel = new Task($GLOBALS['db']);

        // Получаем все задачи для админа, иначе только свои
        if ($user['role'] === 'admin') {
            $tasks = $taskModel->getAll();
        } else {
            $tasks = $taskModel->getByUser($user['id']);
        }

        // Для каждой задачи получаем привязанных менеджеров
        foreach ($tasks as &$task) {
            $task['managers'] = $taskModel->getManagersForTask($task['id']);
        }

        View::render('home/dashboard', [
            'title' => 'Панель управления',
            'tasks' => $tasks,
        ]);
    }

    public function index(): void {
        View::render('home/index', [
            'title' => 'Добро пожаловать'
        ]);
    }
}

