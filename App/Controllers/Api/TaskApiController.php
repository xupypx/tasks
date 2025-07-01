<?php
namespace App\Controllers\Api;

use App\Models\Task;

class TaskApiController {

    public function index(): void {
        header('Content-Type: application/json');
        $taskModel = new Task($GLOBALS['db']);
        $tasks = $taskModel->getAll();
        echo json_encode([
            'success' => true,
            'data' => $tasks
        ]);
    }

    public function show(int $id): void {
        header('Content-Type: application/json');
        $taskModel = new Task($GLOBALS['db']);
        $task = $taskModel->getById($id);

        if ($task) {
            echo json_encode([
                'success' => true,
                'data' => $task
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Задача не найдена'
            ]);
        }
    }

public function store(): void {
    header('Content-Type: application/json');

    // Проверка метода
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешён']);
        exit;
    }

    // Получение JSON тела запроса
    $input = json_decode(file_get_contents('php://input'), true);

    // Валидация полей
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $project_id = (int)($input['project_id'] ?? 0);
    $status = trim($input['status'] ?? 'новая');
    $created_by = current_user()['id'] ?? 1; // временно

    if ($title === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Поле title обязательно']);
        exit;
    }

    // Создание задачи
    $taskModel = new Task($GLOBALS['db']);
    $taskId = $taskModel->create([
        'project_id' => $project_id,
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'created_by' => $created_by,
    ]);

    // Получаем созданную задачу для возврата
    $task = $taskModel->getById($taskId);

    // Ответ с данными о созданной задаче
    http_response_code(201);
    echo json_encode([
        'message' => 'Задача создана',
        'task' => $task // Возвращаем полные данные о задаче
    ]);
    exit;
}



}
