<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\Solution;
use core\View;
use core\PaginationService;
use function current_user;
use function require_login;

class HomeController
{
    public function dashboard(): void
    {
        require_login();
        $user = current_user();

        if (!$user || !isset($user['id'])) {
            http_response_code(403);
            exit('Вы должны быть авторизованы');
        }

        try {
            $db = db();
            $taskModel = new Task($db);
            $projectModel = new Project($db);
            $solutionModel = new Solution($db);

            $perPage = config('pagination.dashboard_show_tasks', 6);

            if ($user['role'] === 'admin') {
                $tasksCount = $taskModel->countAll();
                $pagination = PaginationService::create($tasksCount, $perPage);
                $tasks = $taskModel->getAllPaginated($pagination['per_page'], $pagination['offset']);
            } else {
                $tasksCount = $taskModel->countAssignedToUser((int)$user['id']);
                $pagination = PaginationService::create($tasksCount, $perPage);
                $tasks = $taskModel->getAssignedToUserPaginated((int)$user['id'], $pagination['per_page'], $pagination['offset']);
            }

            // Добавляем проекты и менеджеров
            foreach ($tasks as &$task) {
                $task['project'] = $projectModel->getById((int)$task['project_id']);
                $task['project_title'] = $task['project']['title'] ?? null;
                $task['project_status'] = $task['project']['status'] ?? null;
                $task['project_deadline'] = $task['project']['deadline'] ?? null;
                $task['managers'] = $taskModel->getManagersForTask((int)$task['id']);
                $task['solution_count'] = $solutionModel->countByTaskId((int)$task['id']);
            }
            unset($task);

            View::render('home/dashboard', [
                'title' => 'Панель управления',
                'tasks' => $tasks,
                'tasksCount' => $tasksCount,
                'pagination' => $pagination,
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            exit('Произошла ошибка: ' . $e->getMessage());
        }
    }

    public function index(): void
    {
        View::render('home/index', [
            'title' => 'Добро пожаловать',
        ]);
    }
}

// ✅ Теперь контроллер использует новые методы countAssignedToUser и getAssignedToUserPaginated для менеджеров, а админ видит все задачи с пагинацией. Код чист и готов к тесту.
