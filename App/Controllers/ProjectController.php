<?php

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use App\Models\Project;
use App\Models\Task;
use core\View;
use core\PaginationService;
use PDO;
use RuntimeException;
use Exception;
use function verify_csrf_token;
use function current_user;

class ProjectController
{
private Project $projectModel;
private Task $taskModel;

public function __construct()
{
    $this->projectModel = new Project(db());
    $this->taskModel = new Task(db());
}


/**
 * Список всех проектов с фильтром и пагинацией
 */
public function list(): void
{
    $db = db();
    $status = $_GET['status'] ?? '';

    // --- Универсальный фильтр ---
    $filter = new \core\FilterService($db, 'projects');

    if ($status) {
        $filter->addFilter('status', $status);
    }

    // --- Сортировка по приоритету статуса и дате создания ---
    $filter->setOrderBy([
        "(CASE
            WHEN status IN ('новая', 'новый') THEN 1
            WHEN status = 'в работе' THEN 2
            WHEN status IN ('завершена', 'завершен') THEN 3
            ELSE 4
        END)" => 'ASC',
        'created_at' => 'DESC'
    ]);

    // --- Количество проектов для пагинации ---
    $totalProjects = $filter->countResults();

    $perPage = config('pagination.projects_per_page', 6);
    // --- Пагинация ---
    $pagination = \core\PaginationService::create($totalProjects, $perPage);

    // --- Получаем проекты с пагинацией ---
    $projects = $filter->getResultsPaginated($pagination['per_page'], $pagination['offset']);

    // --- Рендер ---
    View::render('projects/list', [
        'title' => 'Все проекты',
        'projects' => $projects,
        'pagination' => $pagination,
        'status' => $status, // передаём в View для сохранения выбранного фильтра
    ]);
}


public function createForm(): void
{
    $this->ensureAdminAccess();
    $this->ensureCsrfToken();
    
    View::render('projects/create', [
        'title' => 'Создание проекта',
        'csrf_token' => $_SESSION['csrf_token'],
    ]);
}

public function create(): void
{
    $this->ensureAdminAccess();
    $this->ensurePostMethod();
    $this->validateCsrfToken();

    $data = $this->getValidatedProjectData($_POST);

    $projectId = $this->projectModel->create($data);

    if ($projectId > 0) {
        $this->redirectWithSuccess('/projects', 'Проект успешно создан');
    } else {
        $this->redirectWithError('/projects/create', 'Не удалось создать проект');
    }
}

public function show(int $id): void
{
    $projectModel = new Project(db());
    $project = $projectModel->getById($id);

    if (!$project) {
        $_SESSION['error'] = 'Проект не найден';
        header('Location: /projects');
        exit;
    }

    $taskModel = new Task(db());

    // Получаем общее количество задач проекта
    $tasksCount = $taskModel->countByProject($id);

    // Загружаем настройки из конфига
    // $perPage = config('pagination')['projects_show_tasks'] ?? 6; 
    $perPage = config('pagination.projects_show_tasks', 6);   

    // Используем PaginationService
    $pagination = \core\PaginationService::create($tasksCount, $perPage);

    // Получаем задачи текущей страницы
    $tasks = $taskModel->getByProjectPaginated($id, $pagination['per_page'], $pagination['offset']);

    View::render('projects/show', [
        'layout' => 'dashboard',
        'project' => $project,
        'tasks' => $tasks,
        'tasksCount' => $tasksCount,
        'pagination' => $pagination,
        'title' => 'Проект: ' . $project['title'],
    ]);
}



public function edit(int $id): void
{
        (new RoleMiddleware(['role' => 'admin']))->handle();
    $projectModel = new Project(db());
    $project = $projectModel->getById($id);

    if (!$project) {
        http_response_code(404);
        View::render('errors/404');
        exit;
    }

    // Генерация CSRF токена при необходимости
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    View::render('projects/edit', [
        'layout' => 'dashboard',
        'project' => $project,
        'csrf_token' => $_SESSION['csrf_token'],
        'title' => 'Редактировать проект: ' . htmlspecialchars($project['title'])
    ]);
}

public function update(int $id): void
{
// Проверка запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Метод не разрешён');
}

// Проверка CSRF
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    http_response_code(403);
    exit('Неверный CSRF токен');
}

// Получаем текущего пользователя
$user = current_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    $_SESSION['error'] = 'Доступ запрещён: только администратор может редактировать проекты.';
    header("Location: /projects/edit/$id");
    exit;
}

// Подготовка данных
$data = [
    'title' => trim($_POST['title'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'status' => trim($_POST['status'] ?? 'новый'),
    'deadline' => $_POST['deadline'] ?? null,
];

// Обновляем проект через модель
$projectModel = new \App\Models\Project(db());
$projectModel->update($id, $data);
$_SESSION['success'] = 'Проект обновлен!';
// Перенаправляем обратно на страницу проекта
header("Location: /projects");
exit;
}


public function delete(int $id): void
{
(new RoleMiddleware(['role' => 'admin']))->handle();
    $this->ensureAdminAccess();
    $this->ensurePostMethod();
    $this->validateCsrfToken();

    try {
        $project = $this->projectModel->getById($id);
        if (!$project) {
            $this->notFound('Проект не найден');
        }

        $this->deleteProjectWithTasks($id);
        
        $this->redirectWithSuccess(
            '/projects',
            sprintf('Проект "%s" и все связанные задачи успешно удалены', 
                htmlspecialchars($project['title']))
        );
        
    } catch (Exception $e) {
        $this->redirectWithError(
            "/projects/$id",
            'Ошибка при удалении проекта: ' . $e->getMessage()
        );
    }
}

private function deleteProjectWithTasks(int $projectId): void
{
    $tasks = $this->taskModel->getByProjectId($projectId);

    foreach ($tasks as $task) {
        $this->taskModel->deleteTaskWithRelations($task['id']);
    }

    if (!$this->projectModel->delete($projectId)) {
        throw new RuntimeException("Не удалось удалить проект");
    }
}

private function getValidatedProjectData(array $postData): array
{
    $title = trim($postData['title'] ?? '');
    if ($title === '') {
        $this->redirectWithError('/projects/create', 'Название проекта обязательно');
    }

    return [
        'title' => $title,
        'description' => trim($postData['description'] ?? ''),
        'status' => $postData['status'] ?? 'новый',
        'deadline' => $this->parseDeadline($postData['deadline'] ?? null),
        'created_by' => current_user()['id'] ?? null,
    ];
}

private function parseDeadline(?string $deadline): ?string
{
    if (!$deadline) {
        return null;
    }

    try {
        $deadline = str_replace('T', ' ', $deadline);
        $date = new \DateTime($deadline);
        return $date->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
        $this->redirectWithError('/projects/create', 'Неверный формат даты');
    }
}

private function ensureCsrfToken(): void
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

private function validateCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $this->forbidden('Неверный CSRF токен');
    }
}

private function ensurePostMethod(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->methodNotAllowed();
    }
}

private function ensureAdminAccess(): void
{
    $user = current_user();
    
    if (!isset($user) || $user['role'] !== 'admin') {
        $this->redirectWithError(
            '/projects', 
            'Доступ запрещен. Требуются права администратора'
        );
    }
}

private function redirectWithSuccess(string $url, string $message): void
{
    $_SESSION['success'] = $message;
    header("Location: $url");
    exit;
}

private function redirectWithError(string $url, string $message): void
{
    $_SESSION['error'] = $message;
    header("Location: $url");
    exit;
}

private function methodNotAllowed(): void
{
    http_response_code(405);
    View::render('errors/405');
    exit;
}

private function forbidden(string $message): void
{
    http_response_code(403);
    View::render('errors/403', ['message' => $message]);
    exit;
}

private function notFound(string $message): void
{
    http_response_code(404);
    View::render('errors/404', ['message' => $message]);
    exit;
}
}
