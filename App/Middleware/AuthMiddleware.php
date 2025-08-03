<?php

namespace App\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        self::check();
    }

    /**
     * Проверяет авторизацию пользователя.
     *
     * @return void
     */
    public static function check(): void
    {
        self::ensureSessionStarted();

        if (empty($_SESSION['user']['id'])) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '/';
            $_SESSION['error'] = 'Требуется авторизация';
            header('Location: /login');
            exit;
        }
    }

    /**
     * Гарантирует запуск сессии.
     *
     * @return void
     */
    protected static function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
