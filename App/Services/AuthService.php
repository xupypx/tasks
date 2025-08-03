<?php

namespace App\Services;

use PDO;

class AuthService
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Попытка входа пользователя
     * @param string $username
     * @param string $password
     * @return array|string  Данные пользователя при успехе, строка-ошибка при неудаче
     */
    public function attemptLogin(string $username, string $password): array|string
    {
        if ($username === '' || $password === '') {
            return 'Введите логин и пароль';
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            return 'Пользователь не найден';
        }

        if (!password_verify($password, $user['password_hash'])) {
            return 'Неверный пароль';
        }

        // Успешный вход
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];
    }

    /**
     * Создание нового пользователя
     * @param string $username
     * @param string $password
     * @param string $role
     * @return bool
     */
    public function createUser(string $username, string $password, string $role = 'user'): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, role, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$username, $hash, $role]);
    }

    /**
     * Проверка существования пользователя
     * @param string $username
     * @return bool
     */
    public function userExists(string $username): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return (bool) $stmt->fetch();
    }

    /**
     * Проверка авторизации пользователя
     * @return bool
     */
    public function check(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Получение текущего пользователя
     * @return array|null
     */
    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Выход пользователя и уничтожение сессии
     * @return void
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params["path"],
                    'domain' => $params["domain"],
                    'secure' => $params["secure"],
                    'httponly' => $params["httponly"],
                    'samesite' => 'Lax'
                ]
            );
        }

        session_destroy();
    }
}
