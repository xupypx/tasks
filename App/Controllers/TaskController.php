<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\Solution;
use core\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use \DateTime;
use function flash;

class TaskController
{
public function __construct()
{
    AuthMiddleware::check(); // проверка авторизации для всех методов контроллера
}

public function create(): void
{
    (new RoleMiddleware(['role' => 'admin']))->handle(); // только админ может создавать задачи

    $userController = new UserController();
    $managers = $userController->getManagers();

    $projectModel = new Project(db());
    $projects = $projectModel->getAll();

    // ✅ Получаем project_id из GET-параметра, если передан
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : '';

    View::render('tasks/create', [
        'layout' => 'dashboard',
        'title' => 'Создать задачу',
        'projects' => $projects,
        'project_id' => $project_id, // передаём в View
        'csrf_token' => $_SESSION['csrf_token'] ?? '',
        'managers' => $managers
    ]);
}


public function store(): void
{
    $middleware = new RoleMiddleware(['role' => 'admin']);
    $middleware->handle();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Метод не разрешён');
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        exit('Неверный CSRF токен');
    }

    $current = current_user();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $project_id = (int) ($_POST['project_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'новая');
    $managers = $_POST['managers'] ?? [];

    // Валидация дедлайна при создании
    $deadline = $_POST['deadline'] ?? null;
    if (empty($deadline)) {
        $deadline = null;
    } else {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $deadline);
        if (!($d && $d->format('Y-m-d\TH:i') === $deadline)) {
            $_SESSION['error'] = 'Некорректный формат даты дедлайна.';
            header('Location: /tasks/create');
            exit;
        }
    }

    if ($title === '') {
        $_SESSION['error'] = 'Название задачи обязательно';
        header('Location: /tasks/create');
        exit;
    }

    $taskModel = new Task(db());
    $allowedStatuses = ['новая', 'в работе', 'выполнена'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'новая';
    }

    $taskId = $taskModel->create([
        'project_id' => $project_id,
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'deadline' => $deadline,   // <--- добавлено
        'created_by' => $current['id'],
    ]);

    if (!empty($managers)) {
        $taskModel->assignManagers($taskId, $managers);
    }

    header("Location: /tasks/success/$taskId");
    exit;
}


public function success(int $id): void
{
    $taskModel = new Task(db());
    $task = $taskModel->getById($id);

    if (!$task) {
        http_response_code(404);
        exit('Задача не найдена');
    }

    $managers = $taskModel->getManagersForTask($id);
    $project_id = $taskModel->getByIdWithProject($id);

    View::render('tasks/success', [
        'layout' => 'dashboard',
        'task' => $task,
        'managers' => $managers,
        'project_title' => $project_id,
        'title' => 'Задача успешно создана: ' . $task['title'],
    ]);
}

public function list(): void
{
    $db = db();
    $taskModel = new Task($db);
    $projectModel = new Project($db);
    $solutionModel = new Solution($db);

    // --- Получаем фильтр статуса из GET ---
    $status = $_GET['status'] ?? '';

    // --- Получаем общее количество задач с учётом фильтра ---
    $filter = new \core\FilterService($db, 'tasks');
    if ($status) {
        $filter->addFilter('status', $status);
    }

    // --- Добавляем сортировку по приоритету статуса и дате создания ---
    $filter->setOrderBy([
        "(CASE
            WHEN status IN ('новая', 'новый') THEN 1
            WHEN status = 'в работе' THEN 2
            WHEN status IN ('завершена', 'завершен') THEN 3
            ELSE 4
        END)" => 'ASC',
        'created_at' => 'DESC'
    ]);

    // --- Получаем общее количество задач для пагинации ---
    $tasksCount = $filter->countResults();


    // --- Пагинация ---
    $pagination = \core\PaginationService::create($tasksCount, config('pagination.task_list', 6));

    // --- Получаем задачи с пагинацией ---
    $tasks = $filter->getResultsPaginated($pagination['per_page'], $pagination['offset']);

    // --- Получаем менеджеров и проект для каждой задачи ---
    foreach ($tasks as &$task) {
        // Менеджеры задачи
        $task['managers'] = $taskModel->getManagersForTask($task['id']) ?? [];
        $task['solution_count'] = $solutionModel->countByTaskId((int)$task['id']);

        // Проект задачи
        $project = $projectModel->getById($task['project_id']) ?? null;
        $task['project'] = $project ?: [
            'id' => 0,
            'title' => 'Без проекта',
            'description' => '',
            'status' => '',
            'deadline' => null,
            'created_by' => null,
            'created_at' => null,
            'updated_at' => null
        ];
    }
    unset($task);

    // --- Рендерим страницу ---
    View::render('tasks/list', [
        'layout' => 'dashboard',
        'tasks' => $tasks,
        'tasksCount' => $tasksCount,
        'pagination' => $pagination,
        'title' => 'Все задачи',
        'csrf_token' => $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)),
        'status' => $status, // передаём текущий фильтр в View
        'error' => $_SESSION['error'] ?? null,
        'success' => $_SESSION['success'] ?? null,
    ]);

    // --- Очистка flash-сообщений ---
    unset($_SESSION['error'], $_SESSION['success']);
}


public function show(int $id): void
{
    require_login();
    $currentUser = current_user();

    $taskModel = new Task(db());
    $task = $taskModel->getById($id);

    if (!$task) {
        $_SESSION['error'] = 'Задача не найдена';
        header('Location: /tasks/list');
        exit;
    }

    $managers = $taskModel->getManagersForTask($id);

    $solutionModel = new Solution(db());
    $totalSolutions = $solutionModel->countByTask($id);
    $pagination = \core\PaginationService::create($totalSolutions, 3);
    $solutions = $solutionModel->getByTaskPaginated($id, $pagination['per_page'], $pagination['offset']);

    // Проверяем, может ли пользователь добавлять решения
    $canAddSolution = $taskModel->canUserAddSolution($id, $currentUser['id']);

    View::render('tasks/show', [
        'layout' => 'dashboard',
        'task' => $task,
        'project_title' => $task['project_title'] ?? 'Не привязан',
        'managers' => $managers,
        'solutions' => $solutions,
        'current_user' => $currentUser,
        'canAddSolution' => $canAddSolution,
        'pagination' => $pagination,
        'title' => 'Задача: ' . $task['title'],
        'csrf_token' => $_SESSION['csrf_token'] ?? '',
    ]);
}



public function edit(int $id): void
{
    $middleware = new RoleMiddleware(['role' => 'admin']);
    $middleware->handle();
    // RoleMiddleware::check('admin'); // только админ может редактировать задачи

    $taskModel = new Task(db());
    $task = $taskModel->getByIdWithProject($id);

    if (!$task) {
        http_response_code(404);
        View::render('errors/404');
        return;
    }

    $userController = new UserController();
    $projectModel = new Project(db());

    View::render('tasks/edit', [
        'layout' => 'dashboard',
        'task' => $task,
        'managers' => $userController->getManagers(),
        'assignedManagerIds' => array_column($taskModel->getManagersForTask($id) ?? [], 'id'),
        'projects' => $projectModel->getAllProjects() ?? [],
        'current_project_id' => $task['project_id'] ?? null,
        'csrf_token' => $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)),
        'user' => current_user()
    ]);
}

public function update(int $id): void
{
    $middleware = new RoleMiddleware(['role' => 'admin']);
    $middleware->handle();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Метод не разрешён');
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        exit('Неверный CSRF токен');
    }

    // Валидация и обработка дедлайна
    $deadline = $_POST['deadline'] ?? null;

    if (empty($deadline)) {
        $deadline = null;
    } else {
        // Создаем объект DateTime из строки
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $deadline);

        // Проверяем корректность формата даты
        if (!($d && $d->format('Y-m-d\TH:i') === $deadline)) {
            flash('error', 'Некорректный формат даты дедлайна.');
            $this->redirectBack();
        }

        // Получаем текущую дату и время
        $currentDateTime = new DateTime();

        // Добавляем 24 часа к текущему времени
        $minAllowedDateTime = clone $currentDateTime;
        $minAllowedDateTime->modify('+24 hours');

        // Проверяем, что дедлайн не меньше минимально допустимого времени
        if ($d < $minAllowedDateTime) {
            flash('error', 'Дедлайн должен быть не раньше, чем через 24 часа.');
            $this->redirectBack();
        }
    }

    try {
        $taskModel = new Task(db());
        $taskModel->update($id, [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status' => trim($_POST['status'] ?? 'новая'),
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'deadline' => $deadline,
        ]);

        if (!empty($_POST['managers'])) {
            $taskModel->assignManagers($id, $_POST['managers']);
        }

        // Добавляем сообщение об успешном обновлении
        flash('success', 'Задача успешно обновлена!');

        header("Location: /tasks/$id");
        exit;
    } catch (\Exception $e) {
        flash('error', 'Произошла ошибка при обновлении задачи.');
        header("Location: /tasks/$id");
        exit;
    }
}



public function delete(int $id): void
{
    $middleware = new RoleMiddleware(['role' => 'admin']);
    $middleware->handle();
    // RoleMiddleware::check('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Метод не разрешён');
    }

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Неверный CSRF токен');
    }

    $taskModel = new Task(db());
    $task = $taskModel->getById($id);

    if (!$task) {
        $_SESSION['error'] = 'Задача не найдена';
        header('Location: /tasks/list');
        exit;
    }

    $taskModel->deleteTaskWithRelations($id);
    $taskModel->delete($id);

    $_SESSION['success'] = 'Задача удалена успешно';
    header('Location: /tasks/list');
    exit;
}

public function getProjectTasks(int $projectId): void
{
    $tasks = (new Task(db()))->getByProjectId($projectId) ?? [];

    View::render('tasks/project_tasks', [
        'layout' => 'dashboard',
        'tasks' => $tasks,
        'title' => 'Задачи проекта',
    ]);
}


public function myTasks(): void
{
    $current = current_user();
    $taskModel = new Task(db());

    View::render('tasks/my_tasks', [
        'layout' => 'dashboard',
        'title' => 'Мои задачи',
        'myTasks' => $taskModel->getTasksCreatedByUser($current['id']),
        'assignedTasks' => $taskModel->getTasksAssignedToUser($current['id']),
    ]);
}

/**
* Редирект назад
*/
private function redirectBack(): void
{
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;
}
// public function dashboard(): void
//     {
//         $taskModel = new Task(db());

//         // Пагинация
//         $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
//         $perPage = 4;
//         $offset = ($page - 1) * $perPage;

//         // Получаем задачи с пагинацией
//         $tasks = $taskModel->getAllPaginated($perPage, $offset) ?? [];
//         $tasksCount = $taskModel->countAll();
//         $totalPages = ceil($tasksCount / $perPage);

//         // Загружаем менеджеров и проект для каждой задачи
//         $projectModel = new \App\Models\Project(db());
//         foreach ($tasks as &$task) {
//             $task['managers'] = $taskModel->getManagersForTask($task['id']) ?? [];
//             $task['project'] = $projectModel->getById($task['project_id']) ?? null;
//         }
//         unset($task);

//         View::render('home/dashboard', [
//             'tasks' => $tasks,
//             'tasksCount' => $tasksCount,
//             'page' => $page,
//             'totalPages' => $totalPages,
//         ]);
//     }



}
