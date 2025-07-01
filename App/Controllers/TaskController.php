<?php
namespace App\Controllers;

use App\Models\Task;
use core\View;
use function \current_user;
use function \verify_csrf_token;
use function \is_logged_in;

class TaskController {

// Создание задачи
public function create(): void {
    // Получаем список менеджеров
    $userController = new UserController();
    $managers = $userController->getManagers();

    // Передаем в представление
    View::render('tasks/create', [
        'layout' => 'dashboard', // или auth, error, main
        'title' => 'Создать задачу',
        'csrf_token' => $_SESSION['csrf_token'] ?? '',
        'managers' => $managers
    ]);
}


public function store(): void {
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
    if (!$current || !isset($current['id'])) {
        http_response_code(403);
        exit('Вы должны быть авторизованы для создания задачи');
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $project_id = (int) ($_POST['project_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'новая');
    $created_by = (int) $current['id'];
    $managers = $_POST['managers'] ?? [];

    if ($title === '') {
        $_SESSION['error'] = 'Название задачи обязательно';
        header('Location: /tasks/create');
        exit;
    }

    $taskModel = new Task($GLOBALS['db']);
    $allowedStatuses = ['новая', 'в работе', 'выполнена'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'новая';
    }

    $taskId = $taskModel->create([
        'project_id' => $project_id,
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'created_by' => $created_by,
    ]);

    if (!empty($managers)) {
        $taskModel->assignManagers($taskId, $managers);
    }

    header("Location: /tasks/success/$taskId");
    exit;
}

public function success(int $id): void {
    $taskModel = new Task($GLOBALS['db']);
    $task = $taskModel->getById($id); // Получаем задачу по ID

    if (!$task) {
        http_response_code(404);
        exit('Задача не найдена');
    }
    // Получаем менеджеров для этой задачи
    $managers = $taskModel->getManagersForTask($id);

    // Выводим сообщение об успешном создании задачи
    View::render('tasks/success', [
        'layout' => 'dashboard', // или auth, error, main
        'task' => $task,
        'managers' => $managers,
        'title' => 'Задача успешно создана: ' . $task['title'],
    ]);
}

public function list(): void
{
    try {
        $taskModel = new Task($GLOBALS['db']);

        // Получаем задачи с защитой от null
        $tasks = $taskModel->getAll() ?? [];

        // Проверяем, есть ли менеджеры для каждой задачи
        foreach ($tasks as &$task) {
            $taskId = $task['id'] ?? 0;
            $task['managers'] = $taskModel->getManagersForTask($taskId) ?? []; // Защита от null
        }
        unset($task); // Разрываем ссылку

        // Обработка flash-сообщений
        $error = $_SESSION['error'] ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        // Проверяем, что данные в $tasks действительно есть
        error_log(print_r($tasks, true)); // Логирование данных

        View::render('tasks/list', [
            'layout' => 'dashboard',
            'tasks' => $tasks,
            'title' => 'Все задачи',
            'error' => $error,
            'success' => $success,
            'csrf_token' => $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32))
        ]);

    } catch (PDOException $e) {
        error_log('Database error in TaskController::list: ' . $e->getMessage());
        $_SESSION['error'] = 'Ошибка при загрузке задач';
        header('Location: /tasks');
        exit;
    } catch (Exception $e) {
        error_log('Error in TaskController::list: ' . $e->getMessage());
        $_SESSION['error'] = 'Произошла непредвиденная ошибка';
        header('Location: /tasks');
        exit;
    }
}



public function show(int $id): void {
    $taskModel = new Task($GLOBALS['db']);
    $task = $taskModel->getById($id); // Получаем задачу по ID

    if (!$task) {
        $_SESSION['error'] = 'Задача не найдена'; // Добавляем ошибку в сессию
        header('Location: /tasks/list'); // Перенаправляем на список задач
        exit;
    }

    // Получаем менеджеров для этой задачи
    $managers = $taskModel->getManagersForTask($id);

    // Передаем данные в представление
    View::render('tasks/show', [
        'layout' => 'dashboard', // или auth, error, main
        'task' => $task,
        'managers' => $managers,
        'title' => 'Задача: ' . $task['title'],
    ]);
}




// Этот метод будет получать задачу по ID, отображать её в форме, где пользователь сможет изменить данные.
public function edit(int $id): void
{
    $taskModel = new Task($GLOBALS['db']);
    $task = $taskModel->getById($id);

    if (!$task) {
        http_response_code(404);
        View::render('errors/404');
        return;
    }

    // Получаем всех менеджеров
    $userController = new UserController();
    $allManagers = $userController->getManagers() ?? [];

    // Получаем привязанных менеджеров
    $assignedManagers = $taskModel->getManagersForTask($id) ?? [];
    $assignedManagerIds = array_column($assignedManagers, 'id') ?? [];

    // Генерация CSRF токена при необходимости
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Получаем текущего пользователя
    $currentUser = current_user(); // предполагается, что функция есть в helpers.php

    // Передаем данные в представление
    View::render('tasks/edit', [
        'layout' => 'dashboard', // или auth, error, main
        'task' => $task,
        'managers' => $allManagers,
        'assignedManagerIds' => $assignedManagerIds,
        'csrf_token' => $_SESSION['csrf_token'],
        'user' => $currentUser // передаем пользователя
    ]);
}




// Этот метод будет получать обновлённые данные из формы и сохранять их в базе данных.
public function update(int $id): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Метод не разрешён');
    }

    // Проверка CSRF токена
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        exit('Неверный CSRF токен');
    }

    // Получаем данные из формы
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'новая');
    $managers = $_POST['managers'] ?? [];  // Массив ID выбранных менеджеров

    // Обновляем задачу в базе данных
    $taskModel = new Task($GLOBALS['db']);
    $taskModel->update($id, [
        'title' => $title,
        'description' => $description,
        'status' => $status,
    ]);

    // Привязываем менеджеров
    if (!empty($managers)) {
        $taskModel->assignManagers($id, $managers);
    }

    // Перенаправляем на страницу задачи
    header("Location: /tasks/$id");
    exit;
}



public function delete(int $id): void {
    require_login();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Метод не разрешён');
    }

    // Проверка CSRF токена
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        exit('Неверный CSRF токен');
    }

    $user = current_user();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Доступ запрещён: только администратор может удалять задачи.');
    }

    $taskModel = new Task($GLOBALS['db']);

    // Проверяем существование задачи перед удалением
    $task = $taskModel->getById($id);
    if (!$task) {
        $_SESSION['error'] = 'Задача не найдена';
        header('Location: /tasks/list');
        exit;
    }

    // Удаляем привязки менеджеров
    $taskModel->removeManagersFromTask($id);

    // Удаляем задачу
    $taskModel->delete($id);

    $_SESSION['success'] = 'Задача удалена успешно';
    header('Location: /tasks/list');
    exit;
}


}
