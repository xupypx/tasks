<?php

namespace App\Controllers;

use core\View;
use PDO;
use function verify_csrf_token;

class ProjectController
{
    public function index(): void
    {
        global $db;

        // Получаем все проекты
        $stmt = $db->query("SELECT * FROM projects ORDER BY created_at DESC");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('projects/index', [
            'title' => 'Все проекты',
            'projects' => $projects
        ]);
    }

    public function createForm(): void
    {
        View::render('projects/create', [
            'title' => 'Создание проекта',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Метод не разрешён');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            exit('Неверный CSRF токен');
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '') {
            $_SESSION['error'] = 'Название проекта обязательно';
            header('Location: /projects/create');
            exit;
        }

        global $db;
        $stmt = $db->prepare("INSERT INTO projects (title, description, created_by, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$title, $description, $_SESSION['user']['id'] ?? null]);

        header('Location: /projects');
        exit;
    }

    public function show($id): void
    {
        global $db;

        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            http_response_code(404);
            exit('Проект не найден');
        }

        View::render('projects/show', [
            'title' => 'Детали проекта',
            'project' => $project
        ]);
    }

    public function delete($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Метод не разрешён');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            exit('Неверный CSRF токен');
        }

        global $db;
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: /projects');
        exit;
    }
}
