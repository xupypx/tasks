<?php
namespace App\Models;

class Project {
    private $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM projects");
        return $stmt->fetchAll() ?? [];
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }


    public function getTasks(int $projectId): array {
        $stmt = $this->db->prepare("
            SELECT t.*,
                u.username as author_name,
                GROUP_CONCAT(m.user_id) as manager_ids
            FROM tasks t
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN task_managers m ON t.id = m.task_id
            WHERE t.project_id = ?
            GROUP BY t.id
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function userHasAccess(int $projectId, int $userId): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM project_members
            WHERE project_id = ? AND user_id = ?
        ");
        $stmt->execute([$projectId, $userId]);
        return (bool)$stmt->fetch();
    }


//закрытие class Project
}
