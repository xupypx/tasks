<?php
namespace App\Controllers;

use core\View;
use PDO;
use function verify_csrf_token;

class AuthController {
    public function registerForm(): void {
        View::render('auth/register', [
            'title' => 'Регистрация',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Метод не разрешён');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            exit('Неверный CSRF токен');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || strlen($password) < 4) {
            $_SESSION['error'] = 'Имя пользователя и пароль обязательны';
            header('Location: /register');
            exit;
        }

        global $db;
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Пользователь уже существует';
            header('Location: /register');
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)")
           ->execute([$username, $hash]);

        header('Location: /login');
        exit;
    }

    public function loginForm(): void {
        View::render('auth/login', [
            'title' => 'Вход',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Метод не разрешён');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            exit('Неверный CSRF токен');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        global $db;
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = 'Неверный логин или пароль';
            header('Location: /login');
            exit;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];

        $redirect = $_SESSION['redirect_url'] ?? '/dashboard';
        unset($_SESSION['redirect_url']);
        header("Location: $redirect");
        exit;
    }

    public function logout(): void {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
