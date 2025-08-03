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

/**
 * Метод для отображения панели управления
 */
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

        $userId = (int) $user['id'];

        // Получаем общее количество задач
        $tasksCount = $this->getTasksCount($user, $taskModel);

        // Загружаем настройки из конфига
        $perPage = config('pagination.dashboard_show_tasks', 6); 

        // Создаём пагинацию
        $pagination = PaginationService::create($tasksCount, $perPage);

        // Получаем задачи для пользователя (учитывает роль)
        $tasks = $this->getTasksForUser($user, $taskModel, $pagination, $projectModel);

        // Добавляем проекты и менеджеров
        $tasks = $this->enrichTasksWithProjects($tasks, $projectModel);
        $this->addManagersToTasks($tasks, $taskModel);

        // Добавляем количество решений к каждой задаче
        foreach ($tasks as &$task) {
            $task['solution_count'] = $solutionModel->countByTaskId((int) $task['id']);
        }
        unset($task);

        // Рендерим панель управления
        View::render('home/dashboard', [
            'title' => 'Панель управления',
            'tasks' => $tasks,
            'tasksCount' => $tasksCount,
            'pagination' => $pagination,
            // 'myTasks' и 'assignedTasks' удалены как ненужные в текущей архитектуре
        ]);

    } catch (\Throwable $e) {
        http_response_code(500);
        exit('Произошла ошибка: ' . $e->getMessage());
    }
}


    /**
     * Получает количество задач в зависимости от роли пользователя
     *
     * @param array $user
     * @param Task $taskModel
     * @return int
     */
    private function getTasksCount(array $user, Task $taskModel): int
    {
        return $user['role'] === 'admin'
            ? $taskModel->countAll()
            : $taskModel->countByUser((int) $user['id']);
    }

    /**
     * Получает задачи для пользователя с учётом роли и пагинации
     *
     * @param array $user
     * @param Task $taskModel
     * @param array $pagination
     * @return array
     */
private function getTasksForUser(
    array $user,
    Task $taskModel,
    array $pagination,
    Project $projectModel
): array {
    if ($user['role'] === 'admin') {
        $tasks = $taskModel->getAllPaginated(
            $pagination['per_page'],
            $pagination['offset']
        );

        return $this->enrichTasksWithProjects($tasks, $projectModel);
    }

    // Получаем созданные задачи
    $createdTasks = $taskModel->getByUserWithProjectsPaginated(
        (int)$user['id'],
        $pagination['per_page'],
        $pagination['offset']
    );

    // Получаем назначенные задачи
    $assignedTasks = $taskModel->getTasksAssignedToUser((int)$user['id']);

    // Объединяем
    $tasks = array_merge($createdTasks, $assignedTasks);

    return $tasks;
}

    /**
     * Добавляет данные проектов к задачам
     *
     * @param array $tasks
     * @param Project $projectModel
     * @return array
     */
    private function enrichTasksWithProjects(array $tasks, Project $projectModel): array
    {
        foreach ($tasks as &$task) {
            $task['project'] = $projectModel->getById((int) $task['project_id']);
            $task['project_title'] = $task['project']['title'] ?? null;
            $task['project_status'] = $task['project']['status'] ?? null;
            $task['project_deadline'] = $task['project']['deadline'] ?? null;
        }
        unset($task);

        return $tasks;
    }

    /**
     * Добавляет данные менеджеров к задачам
     *
     * @param array $tasks
     * @param Task $taskModel
     * @return void
     */
    private function addManagersToTasks(array &$tasks, Task $taskModel): void
    {
        foreach ($tasks as &$task) {
            $task['managers'] = $taskModel->getManagersForTask((int) $task['id']);
        }
        unset($task);
    }

    /**
     * Главная страница (приветствие)
     *
     * @return void
     */
    public function index(): void
    {
        View::render('home/index', [
            'title' => 'Добро пожаловать',
        ]);
    }
}
