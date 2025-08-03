<?php

namespace App\Models;

use core\Model;
use core\FieldTypeRegistry;
use PDO;
use PDOException;

class Project extends Model
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        if (!$db instanceof PDO) {
            throw new \InvalidArgumentException('Database connection must be an instance of PDO.');
        }
        $this->db = $db;

        // Регистрация типов полей
        FieldTypeRegistry::register('repeater', 'core\\RepeaterField');
        FieldTypeRegistry::register('fileupload', 'core\\FileUploadField');
    }

    protected static $table = 'projects';

    protected static $fields = [
        'title' => ['type' => 'string'],
        'description' => ['type' => 'text'],
        'status' => ['type' => 'string'],
        'deadline' => ['type' => 'datetime'],
    ];

public function create(array $data): int
{
    try {
        $stmt = $this->db->prepare("
            INSERT INTO projects (title, description, status, deadline, created_by, created_at)
            VALUES (:title, :description, :status, :deadline, :created_by, NOW())
        ");
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'] ?? 'новый',
            'deadline' => $data['deadline'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        $projectId = (int)$this->db->lastInsertId();

        if ($projectId <= 0) {
            throw new \Exception("Ошибка при создании проекта.");
        }

        // Обрабатываем динамические поля (если они есть)
        // $this->handleDynamicFields($projectId, $data);

        return $projectId;
    } catch (PDOException $e) {
        error_log("Project creation failed: " . $e->getMessage());
        return 0;
    } catch (\Exception $e) {
        error_log("Unexpected error: " . $e->getMessage());
        return 0;
    }
}


private function handleDynamicFields(int $projectId, array $data): void
{
    foreach ($data as $field => $value) {
        if (isset(static::$fields[$field])) {
            $fieldConfig = static::$fields[$field];

            if (isset($fieldConfig['type'])) {
                $fieldType = $fieldConfig['type'];

                // Обработка поля deadline
                if ($field === 'deadline' && $value) {
                    error_log("Полученная дата: " . $value);  // Для логирования

                    try {
                        // Если дата в формате ISO 8601 без секунд, добавляем их
                        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
                            $value .= ':00';  // Добавляем секунды
                        }

                        if ($this->isValidDateFormat($value)) {
                            // Используем DateTime для правильного парсинга
                            $deadline = new \DateTime($value);
                            $value = $deadline->format('Y-m-d H:i:s');  // Форматируем в базу данных
                        } else {
                            throw new \Exception("Неверный формат даты");
                        }
                    } catch (\Exception $e) {
                        error_log("Ошибка при обработке даты: " . $e->getMessage());
                        $_SESSION['error'] = 'Неверный формат даты';

                        // Отладочный вывод массива
                        // echo '<pre>';
                        // var_dump($data);  // Показываем весь массив данных
                        // echo '</pre>';
                        exit;  // Останавливаем выполнение
                    }
                }

                // Обрабатываем другие поля
                if (in_array($fieldType, ['string', 'text', 'int', 'float', 'boolean'], true)) {
                    $this->saveSimpleField($projectId, $field, $value);
                } else {
                    if (class_exists($fieldType)) {
                        $fieldObject = new $fieldType($this, $field, $fieldConfig);
                        $fieldObject->set($projectId, $value); // Сохраняем с использованием соответствующего класса
                    } else {
                        throw new \Exception("Не найден класс типа поля: {$fieldType}");
                    }
                }
            }
        }
    }
}



    private function saveSimpleField(int $projectId, string $field, $value): void
    {
        $stmt = $this->db->prepare("UPDATE projects SET {$field} = :value WHERE id = :projectId");
        $stmt->execute([
            ':value' => $value,
            ':projectId' => $projectId,
        ]);
    }


    public function getAll(): array
    {
        $sql = "SELECT p.*, COUNT(t.id) as tasks_count
                FROM projects p
                LEFT JOIN tasks t ON t.project_id = p.id
                GROUP BY p.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

public function update(int $id, array $data): void
{
    // Подготовка значений с проверкой наличия ключей
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $status = $data['status'] ?? 'новый';
    $deadline = $data['deadline'] ?? null;

    $stmt = $this->db->prepare("
        UPDATE projects
        SET title = ?, description = ?, status = ?, deadline = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $title,
        $description,
        $status,
        $deadline,
        $id
    ]);
}


    public function getTasks(int $projectId): array
    {
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

    public function userHasAccess(int $projectId, int $userId): bool
    {
        try {
            if ($this->isUserAdmin($userId)) {
                return true;
            }

            $stmt = $this->db->prepare("
                SELECT 1 FROM project_members
                WHERE project_id = ? AND user_id = ?
                LIMIT 1
            ");
            $stmt->execute([$projectId, $userId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Project access check failed: " . $e->getMessage());
            return false;
        }
    }

    private function isUserAdmin(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user && $user['role'] === 'admin';
    }

    public function getAvailableProjects(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.* FROM projects p
            JOIN project_members pm ON p.id = pm.project_id
            WHERE pm.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllProjects(): array
    {
        $stmt = $this->db->query("SELECT id, title FROM projects ORDER BY title");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
public function countAll(): int
{
    $stmt = $this->db->query("SELECT COUNT(*) FROM projects");
    return (int)$stmt->fetchColumn();
}

public function getPaginated(int $limit, int $offset): array
{
    $stmt = $this->db->prepare("
        SELECT p.*,
               (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_count
        FROM projects p
        ORDER BY p.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}


}
