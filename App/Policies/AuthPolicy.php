<?php

namespace App\Policies;

class AuthPolicy
{
    /**
     * Проверяет, является ли пользователь администратором
     *
     * @param array|null $user
     * @return bool
     */
    public static function isAdmin(?array $user): bool
    {
        return $user && ($user['role'] === 'admin');
    }

    /**
     * Проверяет, является ли пользователь исполнителем задачи
     *
     * @param array|null $user
     * @param array $managers
     * @return bool
     */
    public static function isExecutor(?array $user, array $managers): bool
    {
        if (!$user || empty($managers)) {
            return false;
        }

        foreach ($managers as $manager) {
            if (!empty($manager['id']) && $manager['id'] == $user['id']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, может ли пользователь редактировать решение
     *
     * @param array|null $user
     * @param array $managers
     * @return bool
     */
    public static function canEditSolution(?array $user, array $managers): bool
    {
        return self::isAdmin($user) || self::isExecutor($user, $managers);
    }

    /**
     * Проверяет, может ли пользователь удалить решение
     *
     * @param array|null $user
     * @param array $managers
     * @return bool
     */
    public static function canDeleteSolution(?array $user, array $managers): bool
    {
        return self::isAdmin($user) || self::isExecutor($user, $managers);
    }
}
