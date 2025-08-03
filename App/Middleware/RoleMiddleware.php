<?php

namespace App\Middleware;

use Exception;

class RoleMiddleware
{
    protected string $requiredRole = 'admin';
    protected array $user;

    public function __construct(array $params = [])
    {
        if (!empty($params['role'])) {
            $this->requiredRole = $params['role'];
        }

        // Получаем пользователя из сессии
        $this->user = $this->getUserFromSession();
    }

    /**
     * Основной запуск middleware
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $this->checkRole();
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Проверка роли пользователя
     *
     * @return void
     */
    protected function checkRole(): void
    {
        $this->validateUser();
        $this->validateRole();
    }

    /**
     * Проверка наличия авторизованного пользователя
     *
     * @throws Exception
     */
    protected function validateUser(): void
    {
        if (empty($this->user)) {
            throw new Exception('Пользователь не авторизован', 403);
        }
    }

    /**
     * Проверка роли пользователя
     *
     * @throws Exception
     */
    protected function validateRole(): void
    {
        if ($this->user['role'] !== $this->requiredRole) {
            throw new Exception('Недостаточно прав доступа', 403);
        }
    }

    /**
     * Получение пользователя из сессии
     *
     * @return array
     */
    protected function getUserFromSession(): array
    {
        return $_SESSION['user'] ?? [];
    }

    /**
     * Установка требуемой роли
     *
     * @param string $role
     * @return void
     */
    public function setRequiredRole(string $role): void
    {
        $this->requiredRole = $role;
    }

    /**
     * Обработка ошибок
     *
     * @param Exception $exception
     * @return void
     */
    protected function handleError(Exception $exception): void
    {
        http_response_code($exception->getCode());
        flash('error', $exception->getMessage());
        $this->redirectBack();
    }

    /**
     * Перенаправление обратно на предыдущую страницу
     *
     * @return void
     */
    protected function redirectBack(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: $referer");
        exit;
    }
}
