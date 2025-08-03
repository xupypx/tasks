<?php

namespace App\Controllers;

use Core\View;
use App\Models\Task;
use App\Models\Solution;
use PDO;
class AdminController
{
public function users()
{
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        View::render('errors/403');
        exit;
    }

    $db = db();
    $taskModel = new Task($db);
    $solutionModel = new Solution($db);  // Добавляем модель решений

    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $pagination = \core\PaginationService::create($totalUsers, 10);

    $stmt = $db->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

    // Получаем количество задач и решений для каждого пользователя
    foreach ($users as &$user) {
        $user['tasks_count'] = $taskModel->countByUser($user['id']);
        $user['solutions_count'] = $solutionModel->countSolutionsByUser($user['id']);  // Считаем решения
    }
    unset($user);

    View::render('admin/users', [
        'users' => $users,
        'pagination' => $pagination,
        'title' => 'Все пользователи',
    ]);
}



 public function deleteUser($id)
{
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        View::render('errors/403');
        exit;
    }

    $db = db();

    // Проверяем, есть ли задачи у пользователя
    $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE created_by = :id");
    $stmt->execute(['id' => $id]);
    $taskCount = $stmt->fetchColumn();

    if ($taskCount > 0) {
        // Есть задачи – перенаправляем на форму переназначения
        header('Location: /admin/users/reassign/' . $id);
        exit;
    }

    // Удаляем пользователя, если задач нет
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");

    if ($stmt->execute(['id' => $id])) {
        header('Location: /admin/users');
        flash('success', 'Пользователь удален!');
        $this->redirectBack();
    } else {
        http_response_code(500);
        flash('error', 'Ошибка удаления пользователя.');
        $this->redirectBack();
    }
}


public function reassignForm($id)
{
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        View::render('errors/403');
        exit;
    }

    $db = db();

    // Получаем пользователя
    $stmt = $db->prepare("SELECT id, username FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo "Пользователь не найден.";
        exit;
    }

    // Получаем задачи пользователя
    $stmt = $db->prepare("SELECT id, title FROM tasks WHERE created_by = :id");
    $stmt->execute(['id' => $id]);
    $tasks = $stmt->fetchAll();

    // Получаем список других пользователей для переназначения
    $stmt = $db->prepare("SELECT id, username FROM users WHERE id != :id");
    $stmt->execute(['id' => $id]);
    $otherUsers = $stmt->fetchAll();

    View::render('admin/reassign', [
        'user' => $user,
        'tasks' => $tasks,
        'otherUsers' => $otherUsers,
    ]);
}

public function reassign($id)
{
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        View::render('errors/403');
        exit;
    }

    $db = db();

    // Получаем выбранного нового автора из POST
    $newUserId = $_POST['new_user_id'] ?? null;

    if (!$newUserId) {
        echo "Не выбран новый автор.";
        exit;
    }

    // Проверяем, существует ли новый пользователь
    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->execute(['id' => $newUserId]);
    if (!$stmt->fetch()) {
        echo "Выбранный новый пользователь не найден.";
        exit;
    }

    // Переназначаем задачи
    $stmt = $db->prepare("UPDATE tasks SET created_by = :new_user_id WHERE created_by = :old_user_id");
    $stmt->execute([
        'new_user_id' => $newUserId,
        'old_user_id' => $id,
    ]);

    // Удаляем пользователя
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    if ($stmt->execute(['id' => $id])) {
        header('Location: /admin/users');
        exit;
    } else {
        http_response_code(500);
        echo "Ошибка удаления пользователя.";
        exit;
    }
}

}


