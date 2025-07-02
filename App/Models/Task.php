<?php

namespace App\Models;

use PDO;

class Task {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Метод для получения задачи по ID
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM tasks WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; // Возвращаем результат или null, если задача не найдена
    }

    // Метод для получения всех задач
    public function getAll(): array {
        $stmt = $this->db->query('SELECT * FROM tasks');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Метод для создания задачи
    public function create(array $data): int {
        // Создаём задачу без привязки менеджеров
        $stmt = $this->db->prepare("
            INSERT INTO tasks (project_id, title, description, status, created_by)
            VALUES (:project_id, :title, :description, :status, :created_by)
        ");

        $stmt->execute([
            ':project_id' => $data['project_id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':status' => $data['status'],
            ':created_by' => $data['created_by']
        ]);

        // Получаем ID только что созданной задачи
        $taskId = (int) $this->db->lastInsertId();

        // Привязываем менеджеров, если они есть
        if (isset($data['manager_ids']) && !empty($data['manager_ids'])) {
            $this->assignManagers($taskId, $data['manager_ids']);
        }

        return $taskId; // Возвращаем ID только что созданной задачи
    }

    // Метод для обновления задачи в модели
    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare('
            UPDATE tasks
            SET title = :title, description = :description, status = :status
            WHERE id = :id
        ');

        $data['id'] = $id;
        $stmt->execute($data);
    }

    // Метод для удаления задачи в модели
    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }

    // Метод для привязки менеджеров к задаче
    public function assignManagers(int $taskId, array $managerIds): void {
        // Удаляем старые привязки
        $stmt = $this->db->prepare("DELETE FROM task_user WHERE task_id = ?");
        $stmt->execute([$taskId]);

        // Добавляем новые привязки
        foreach ($managerIds as $managerId) {
            $stmt = $this->db->prepare("INSERT INTO task_user (task_id, user_id) VALUES (?, ?)");
            $stmt->execute([$taskId, $managerId]);
        }
    }


    // Логика для получения менеджеров, связанных с задачей
public function getManagersForTask(int $taskId): array
{
    $stmt = $this->db->prepare('
        SELECT u.id, u.username, u.email
        FROM users u
        JOIN task_user tu ON u.id = tu.user_id
        WHERE tu.task_id = :task_id AND u.role = "manager"
    ');
    $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $stmt->execute();

    // Возвращаем менеджеров с id, username и email
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function removeManagersFromTask(int $taskId): void {
    $stmt = $this->db->prepare('DELETE FROM task_user WHERE task_id = :task_id');
    $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $stmt->execute();
}

/**
    * Получить задачи, созданные определённым пользователем
    *
    * @param int $userId
    * @return array
    */
public function getByUser(int $userId): array
{
    $stmt = $this->db->prepare('
        SELECT *
        FROM tasks
        WHERE created_by = :user_id
        ORDER BY created_at DESC
    ');
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function createWithProject(array $data): int {
    $this->db->beginTransaction();

    try {
        $stmt = $this->db->prepare("
            INSERT INTO tasks
            (title, description, status, project_id, created_by, manager_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['status'] ?? 'new',
            $data['project_id'],
            $data['created_by'],
            $data['manager_id'] ?? null
        ]);

        $taskId = $this->db->lastInsertId();

        // Привязка менеджеров если есть
        if (!empty($data['managers'])) {
            $this->assignManagers($taskId, $data['managers']);
        }

        $this->db->commit();
        return $taskId;
    } catch (Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}

public function getByProject(int $projectId): array {
    return $this->getAll(['project_id' => $projectId]);
}

// Закрытие class Task
}
