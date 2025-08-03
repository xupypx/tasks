<?php

namespace App\Controllers;

use Core\View;
use App\Services\AuthService;
use function verify_csrf_token;

class AuthController
{
    protected AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService(db());
    }

    public function registerForm(): void
    {
        View::render('auth/register', [
            'title' => 'Регистрация',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    public function register(): void
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

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || strlen($password) < 4) {
            $_SESSION['error'] = 'Имя пользователя и пароль обязательны';
            header('Location: /register');
            exit;
        }

        if ($this->auth->userExists($username)) {
            $_SESSION['error'] = 'Пользователь уже существует';
            header('Location: /register');
            exit;
        }

        $this->auth->createUser($username, $password);
        header('Location: /login');
        exit;
    }

    public function loginForm(): void
    {
        View::render('auth/login', [
            'title' => 'Вход',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Метод не разрешён');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            // http_response_code(403);
            // exit('Неверный CSRF токен');
            flash('error', 'Неверный CSRF-токен. Пожалуйста, обновите страницу и попробуйте снова. Если проблема сохраняется, выйдите из системы и войдите заново.');
            $this->redirectBack();
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = $this->auth->attemptLogin($username, $password);

        if (is_string($result)) {
            // $_SESSION['error'] = $result;
            // header('Location: /login');
            // exit;
            flash('error', $result);
            $this->redirectBack();
        }

        $_SESSION['user'] = $result;

        $redirect = $_SESSION['redirect_url'] ?? '/dashboard';
        unset($_SESSION['redirect_url']);
        header("Location: $redirect");
        exit;
    }

    public function logout(): void
    {
        // Всегда запускаем сессию
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('auth/logout', [
                'csrf_token' => $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)),
                'title' => 'Подтверждение выхода'
            ]);
            return;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            $_SESSION['error'] = 'Неверный CSRF токен';
            header('Location: /dashboard');
            exit;
        }

        // Используем AuthService для выхода
        $this->auth->logout();

        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        header('Location: /login');
        exit;
    }
/**
* Редирект назад
*/
private function redirectBack(): void
{
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;
}
}
