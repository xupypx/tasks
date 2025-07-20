<?php
namespace App\Models;

use core\Model;
use core\FieldTypeRegistry;
use PDO;

class Task extends Model
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        if (!$db instanceof PDO) {
            throw new \InvalidArgumentException('Database connection must be an instance of PDO.');
        }
        $this->db = $db;

        // Регистрация FieldTypes
        FieldTypeRegistry::register('repeater', 'core\\RepeaterField');
        FieldTypeRegistry::register('fileupload', 'core\\FileUploadField');
    }

    protected static $table = 'tasks';

    protected static $fields = [
        'title' => ['type' => 'string'],
        'description' => ['type' => 'text'],
        'status' => ['type' => 'string'],
        'images' => [
            'type' => 'repeater',  // Это поле будет хранить несколько изображений
            'table' => 'task_images',  // Имя таблицы для изображений
            'foreign_key' => 'task_id',  // Поле внешнего ключа
        ],
        'file' => [
            'type' => 'fileupload',  // Это поле для файлов
            'table' => 'task_files',  // Имя таблицы для файлов
            'foreign_key' => 'task_id',  // Поле внешнего ключа
        ],
    ];

    public function create(array $data): int
    {
        // Сначала создаем задачу
        $stmt = $this->db->prepare("
        INSERT INTO tasks (title, description, status, deadline, created_at, updated_at)
        VALUES (:title, :description, :status, :deadline, NOW(), NOW())"
    );

        $stmt->execute([
            ':project_id' => $data['project_id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':status' => $data['status'],
            ':created_by' => $data['created_by'],
            ':deadline' => $data['deadline'] ?? null,
        ]);

        $taskId = (int) $this->db->lastInsertId();

        // Привязываем менеджеров, если они есть
        if (isset($data['manager_ids']) && !empty($data['manager_ids'])) {
            $this->assignManagers($taskId, $data['manager_ids']);
        }

        // Обрабатываем динамические поля (если они есть)
        $this->handleDynamicFields($taskId, $data);

        return $taskId;
    }

    private function handleDynamicFields(int $taskId, array $data): void
    {
        foreach ($data as $field => $value) {
            if (isset(static::$fields[$field])) {
                $fieldConfig = static::$fields[$field];

                if (isset($fieldConfig['type'])) {
                    $fieldType = $fieldConfig['type'];

                    if ($fieldType === 'repeater') {
                        // Обрабатываем изображения
                        $this->handleRepeaterField($taskId, $field, $value, $fieldConfig['table']);
                    } elseif ($fieldType === 'fileupload') {
                        // Обрабатываем файлы
                        $this->handleFileField($taskId, $field, $value, $fieldConfig['table']);
                    } else {
                        // Обрабатываем обычные поля
                        $this->saveSimpleField($taskId, $field, $value);
                    }
                }
            }
        }
    }

    private function handleRepeaterField(int $taskId, string $field, array $value, string $table): void
    {
        // Здесь логика для добавления изображений в таблицу task_images
        foreach ($value as $item) {
            $stmt = $this->db->prepare("INSERT INTO {$table} (task_id, image_path, caption) VALUES (:task_id, :image_path, :caption)");
            $stmt->execute([
                ':task_id' => $taskId,
                ':image_path' => $item['image_path'],
                ':caption' => $item['caption'] ?? null, // Если есть подпись
            ]);
        }
    }

    private function handleFileField(int $taskId, string $field, array $value, string $table): void
    {
        // Здесь логика для добавления файла в таблицу task_files
        $stmt = $this->db->prepare("INSERT INTO {$table} (task_id, file_path, file_name) VALUES (:task_id, :file_path, :file_name)");
        $stmt->execute([
            ':task_id' => $taskId,
            ':file_path' => $value['file_path'],
            ':file_name' => $value['file_name'],
        ]);
    }

    private function saveSimpleField(int $taskId, string $field, $value): void
    {
        $stmt = $this->db->prepare("UPDATE tasks SET {$field} = :value WHERE id = :taskId");
        $stmt->execute([
            ':value' => $value,
            ':taskId' => $taskId,
        ]);
    }

    // Метод для удаления задачи в модели
    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
public function getAll(array $conditions = []): array
{
    $query = "SELECT * FROM tasks";
    
    // Если есть условия фильтрации
    if ($conditions) {
        $whereClauses = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }
    }
    
    // Если есть параметры, используем prepare
    if ($conditions) {
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $this->db->query($query);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE created_by = :userId");
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // метод для подсчёта задач по проекту
    public function countByProjectId(int $projectId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = :projectId");
        $stmt->execute([':projectId' => $projectId]);
        return (int) $stmt->fetchColumn();
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
    // Метод для получения задачи по ID
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT tasks.*,
                projects.title AS project_title,
                projects.deadline AS project_deadline,
                projects.status AS project_status
            FROM tasks
            LEFT JOIN projects ON tasks.project_id = projects.id
            WHERE tasks.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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

    public function getByProject(int $projectId): array {
        return $this->getAll(['project_id' => $projectId]);
    }

public function getByIdWithProject(int $id): ?array
{
    $stmt = $this->db->prepare("
        SELECT t.*, p.title as project_title
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE t.id = :id
    ");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

    // Метод для обновления задачи в модели
public function update(int $id, array $data): bool
{
    // Добавляем deadline в список разрешённых полей
    $allowedFields = ['title', 'description', 'status', 'project_id', 'deadline'];

    $filteredData = array_intersect_key($data, array_flip($allowedFields));

    if (empty($filteredData)) {
        return false;
    }

    $setParts = [];
    foreach ($filteredData as $key => $value) {
        $setParts[] = "$key = :$key";
    }

    // Добавляем обновление времени изменения задачи
    $setParts[] = "updated_at = NOW()";

    $query = "UPDATE tasks SET " . implode(', ', $setParts) . " WHERE id = :id";
    $stmt = $this->db->prepare($query);

    $filteredData['id'] = $id;

    return $stmt->execute($filteredData);
}

/**
* Удаляет задачу и все связанные с ней данные
* @param int $taskId ID задачи
* @return bool Возвращает true при успешном удалении
* @throws \PDOException В случае ошибки удаления
*/
public function deleteTaskWithRelations(int $taskId): bool
{
    try {
        $this->db->beginTransaction();

        // 1. Удаляем связи с менеджерами
        $this->deleteRelatedData($taskId, 'task_user', 'task_id');

        // 2. Удаляем прикрепленные файлы
        $this->deleteRelatedData($taskId, 'task_files', 'task_id');

        // 3. Удаляем прикрепленные изображения
        $this->deleteRelatedData($taskId, 'task_images', 'task_id');

        // 4. Удаляем саму задачу
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :taskId");
        $stmt->execute([':taskId' => $taskId]);

        $this->db->commit();
        return true;
    } catch (\PDOException $e) {
        $this->db->rollBack();
        throw new \PDOException("Ошибка удаления задачи: " . $e->getMessage());
    }
}

    /**
    * Вспомогательный метод для удаления связанных данных
    */
    private function deleteRelatedData(int $taskId, string $tableName, string $foreignKey): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$tableName} WHERE {$foreignKey} = :taskId");
        $stmt->execute([':taskId' => $taskId]);
    }

public function getAllWithProjects(): array
{
    $stmt = $this->db->query("
        SELECT t.*, p.title as project_title
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getByUserWithProjects(int $userId): array
{
    $stmt = $this->db->prepare("
        SELECT t.*, p.title as project_title
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE t.created_by = :userId
    ");
    $stmt->execute([':userId' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getByProjectId(int $projectId): array
{
    $stmt = $this->db->prepare("SELECT id, title FROM tasks WHERE project_id = :projectId");
    $stmt->execute([':projectId' => $projectId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getTasksCreatedByUser(int $userId): array
{
    $stmt = $this->db->prepare("
        SELECT t.*, p.title AS project_title
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE t.created_by = :userId
    ");
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetchAll();
}

public function getTasksAssignedToUser(int $userId): array
{
    $stmt = $this->db->prepare("
        SELECT t.*, p.title AS project_title
        FROM tasks t
        JOIN task_user tu ON tu.task_id = t.id
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE tu.user_id = :userId
        AND t.created_by != :userId
    ");
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetchAll();
}

// Пагинация задач по проекту
public function getByProjectPaginated(int $projectId, int $perPage, int $offset): array
{
    $stmt = $this->db->prepare("SELECT * FROM tasks WHERE project_id = :project_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countByProject(int $projectId): int
{
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = :project_id");
    $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

public function getAllPaginated(int $perPage, int $offset): array
{
    $stmt = $this->db->prepare("SELECT * FROM tasks ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countAll(): int
{
    $stmt = $this->db->query("SELECT COUNT(*) FROM tasks");
    return (int)$stmt->fetchColumn();
}

/**
 * Получает задачи пользователя с проектами + пагинация
 *
 * @param int $userId
 * @param int $perPage
 * @param int $offset
 * @return array
 */
public function getByUserWithProjectsPaginated(int $userId, int $perPage, int $offset): array
{
    $stmt = $this->db->prepare("
        SELECT t.*, p.title as project_title
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE t.created_by = :userId
        ORDER BY t.created_at DESC
        LIMIT :perPage OFFSET :offset
    ");
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Считает количество задач, в которых пользователь участвует
 * (созданные им или назначенные на него)
 *
 * @param int $userId
 * @return int
 */
public function countByUser(int $userId): int
{
    $stmt = $this->db->prepare("
        SELECT COUNT(DISTINCT t.id)
        FROM tasks t
        LEFT JOIN task_user tu ON t.id = tu.task_id
        WHERE t.created_by = :userId OR tu.user_id = :userId
    ");
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}


}
