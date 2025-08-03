<?php

namespace App\Controllers;

use App\Models\Solution;
use App\Models\Task;
use App\Models\Project;
use core\View;
use function current_user;
use function require_login;
use function verify_csrf_token;
use function flash;

class SolutionController
{
    /**
     * Сохраняет новое решение задачи
     */
    public function store(int $task_id): void
    {
        require_login();
        $user = current_user();
        $this->checkCsrfToken();

        $content = trim($_POST['content'] ?? '');
        if ($content === '') {
            flash('error', 'Содержимое не может быть пустым');
            $this->redirectBack();
        }
        // Проверка прав доступа
        if (!$this->canEditTask($task_id, $user['id'])) {
            flash('error', 'Только назначенные менеджеры могут добавлять решения');
            $this->redirectBack();
        }


        $solutionModel = new Solution(db());
        $solutionModel->create([
            'task_id' => $task_id,
            'user_id' => $user['id'],
            'content' => $content,
            'created_by' => $user['id']
        ]);

        flash('success', 'Решение добавлено');
        $this->redirectBack();
    }

    /**
     * Отображает форму редактирования решения
     */
    public function edit(int $id): void
    {
        require_login();
        $user = current_user();

        $solutionModel = new Solution(db());
        $solution = $solutionModel->getById($id);
        if (!$solution) {
            $this->abort(404, 'Решение не найдено');
        }

        // Проверка прав доступа
        if (!$this->canEdit($solution, $user)) {
            flash('error', 'Вы можете редактировать только свои решения');
            $this->redirectBack();
        }

        // Проверка статуса задачи
        $taskModel = new Task(db());
        $task = $taskModel->getById($solution['task_id']);
        if ($task && $task['status'] === 'завершен') {
            flash('error', 'Проект закрыт. Редактирование решений запрещено.');
            header('Location: /projects');
            exit;
        }

        // Загрузка проекта
        $projectModel = new Project(db());
        $project = $projectModel->getById($task['project_id'] ?? 0);

        // Автор решения
        $authorName = $solutionModel->getAuthorName($solution['created_by']);

        // История правок (опционально для вывода в шаблоне)
        $edits = $solutionModel->getEditsBySolutionId($id);

        View::render('solutions/edit', [
            'layout' => 'dashboard',
            'solution' => $solution,
            'authorName' => $authorName,
            'task' => $task,
            'project' => $project,
            'edits' => $edits,
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Обновляет существующее решение
     */
    public function update(int $id): void
    {
        require_login();
        $user = current_user();
        $this->checkCsrfToken();

        $content = trim($_POST['content'] ?? '');
        if ($content === '') {
            flash('error', 'Содержимое не может быть пустым');
            $this->redirectBack();
        }

        $solutionModel = new Solution(db());
        $solution = $solutionModel->getById($id);
        if (!$solution) {
            flash('error', 'Решение не найдено');
            $this->redirectBack();
        }

        if (!$this->canEdit($solution, $user)) {
            flash('error', 'Вы можете редактировать только свои решения.');
            $this->redirectBack();
        }

        // Проверка статуса задачи
        $taskModel = new Task(db());
        $task = $taskModel->getById($solution['task_id']);
        if ($task && $task['status'] === 'завершен') {
            flash('error', 'Проект закрыт. Изменение решений запрещено.');
            $this->redirectBack();
        }

        // 🚨 Проверка: изменилось ли содержание?
        if ($solution['content'] === $content) {
            flash('info', 'Изменений не обнаружено.');
            $this->redirectBack();
        }

        $adminId = $user['role'] === 'admin' ? $user['id'] : null;

        // Используем новую логику update с логированием изменений
        $solutionModel->update($id, $content, $adminId);

        flash('success', 'Решение успешно отредактировано' . ($adminId ? ' администратором' : ''));
        $this->redirectBack();
    }

    /**
     * Удаляет решение
     */
    public function delete(int $id): void
    {
        require_login();
        $user = current_user();

        $solutionModel = new Solution(db());
        $solution = $solutionModel->getById($id);
        if (!$solution) {
            flash('error', 'Решение не найдено');
            $this->redirectBack();
        }

        if (!$this->canEdit($solution, $user)) {
            flash('error', 'Нет доступа к удалению этого решения');
            $this->redirectBack();
        }

        // Проверка статуса задачи
        $taskModel = new Task(db());
        $task = $taskModel->getById($solution['task_id']);
        if ($task && $task['status'] === 'завершен') {
            flash('error', 'Проект закрыт. Удаление решений запрещено.');
            $this->redirectBack();
        }

        $solutionModel->delete($id);
        flash('success', 'Решение удалено');
        $this->redirectBack();
    }

    /**
     * Проверка CSRF токена
     */
    private function checkCsrfToken(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            flash('error', 'Неверный CSRF токен');
            $this->redirectBack();
        }
    }

    /**
     * Проверка прав редактирования решения
     */
    private function canEdit(array $solution, array $user): bool
    {
        return $solution['user_id'] === $user['id'] || $user['role'] === 'admin';
    }

    /**
     * Редирект назад
     */
    private function redirectBack(): void
    {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    /**
     * Завершает выполнение с указанным статусом и сообщением
     */
    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        exit($message);
    }

/**
 * Проверяет, может ли пользователь добавлять решение для задачи
 */
private function canEditTask(int $task_id, int $user_id): bool
{
    $taskModel = new Task(db());
    return $taskModel->canUserAddSolution($task_id, $user_id);
}
}
