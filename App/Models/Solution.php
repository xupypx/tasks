<?php

namespace App\Models;

use PDO;

class Solution
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function countByTaskId(int $taskId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM solutions WHERE task_id = :task_id");
        $stmt->execute(['task_id' => $taskId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO solutions (task_id, user_id, content, created_by)
            VALUES (:task_id, :user_id, :content, :created_by)
        ");

        $result = $stmt->execute([
            ':task_id' => $data['task_id'],
            ':user_id' => $data['user_id'],
            ':content' => $data['content'],
            ':created_by' => $data['created_by'],
        ]);

        if (!$result) {
            error_log('Solution create error: ' . print_r($stmt->errorInfo(), true));
            return false;
        }

        // ✅ Обновляем статус задачи на "в работе", если ещё не в работе
        $updateTaskStmt = $this->db->prepare("
            UPDATE tasks
            SET status = 'в работе'
            WHERE id = :task_id
            AND status != 'в работе'
        ");
        $updateTaskStmt->execute([':task_id' => $data['task_id']]);

        // ✅ Обновляем статус проекта на "в работе", если хотя бы одна задача с решением
        $updateProjectStmt = $this->db->prepare("
            UPDATE projects
            SET status = 'в работе'
            WHERE id = (
                SELECT project_id FROM tasks WHERE id = :task_id
            )
            AND status != 'в работе'
        ");
        $updateProjectStmt->execute([':task_id' => $data['task_id']]);

        return true;
    }

    public function getByTask(int $task_id): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, u.username 
            FROM solutions s
            JOIN users u ON s.user_id = u.id
            WHERE s.task_id = :task_id
            ORDER BY s.created_at DESC
        ");
        $stmt->execute(['task_id' => $task_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Обновляет решение по ID с логированием изменений
     *
     * @param int $id
     * @param string $newContent
     * @param int $adminId
     * @return bool
     */
    public function update(int $id, string $newContent, ?int $adminId = null): bool
    {
        // Получаем старое содержание
        $oldSolution = $this->getById($id);
        if (!$oldSolution) {
            return false;
        }

        $editedByAdmin = $adminId !== null ? $adminId : null; // ✅ ИЗМЕНЕНО

        // Обновляем решение
        $stmt = $this->db->prepare("
            UPDATE solutions
            SET content = :content,
                updated_at = NOW(),
                edited_by_admin = :edited_by_admin
            WHERE id = :id
        ");

        $result = $stmt->execute([
            ':content' => $newContent,
            ':edited_by_admin' => $editedByAdmin, // ✅ ИЗМЕНЕНО
            ':id' => $id
        ]);

        if (!$result) {
            error_log('Solution update error: ' . print_r($stmt->errorInfo(), true));
            return false;
        }

        // Логируем изменение в solution_edits
        $stmtLog = $this->db->prepare("
            INSERT INTO solution_edits (solution_id, edited_by, old_content, new_content)
            VALUES (:solution_id, :edited_by, :old_content, :new_content)
        ");

        $stmtLog->execute([
            ':solution_id' => $id,
            ':edited_by' => $editedByAdmin, // ✅ ИЗМЕНЕНО
            ':old_content' => $oldSolution['content'],
            ':new_content' => $newContent
        ]);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM solutions WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);

        if (!$result) {
            error_log('Solution delete error: ' . print_r($stmt->errorInfo(), true));
        }

        return $result;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM solutions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $solution = $stmt->fetch(PDO::FETCH_ASSOC);

        return $solution ?: null;
    }

    public function getAuthorName(int $userId): string
    {
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user['username'] ?? 'Неизвестно';
    }

    // Пагинация решений по задаче
    public function getByTaskPaginated(int $taskId, int $perPage, int $offset): array
    {
        $stmt = $this->db->prepare("SELECT solutions.*, users.username
            FROM solutions
            LEFT JOIN users ON solutions.user_id = users.id
            WHERE task_id = :task_id
            ORDER BY solutions.created_at DESC
            LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByTask(int $taskId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM solutions WHERE task_id = :task_id");
        $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function countSolutionsByUser(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM solutions WHERE created_by = :userId");
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Получает историю правок решения
     *
     * @param int $solutionId
     * @return array
     */
    public function getEditsBySolutionId(int $solutionId): array
    {
        $stmt = $this->db->prepare("
            SELECT se.*, u.username AS editor_name
            FROM solution_edits se
            LEFT JOIN users u ON se.edited_by = u.id
            WHERE se.solution_id = :solution_id
            ORDER BY se.edited_at DESC
        ");
        $stmt->execute([':solution_id' => $solutionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
