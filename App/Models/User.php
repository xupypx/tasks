<?php

namespace App\Models;

use PDO;

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Получить пользователя по ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Получить пользователя по email
     */
    public function getByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Получить пользователя по username
     */
    public function getByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Создать нового пользователя
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO users (username, email, password_hash, role)
            VALUES (:username, :email, :password_hash, :role)
        ');

        $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role' => $data['role'] ?? 'user'
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Обновить токен сброса пароля
     */
    public function updateResetToken(int $userId, string $token): void
    {
        $stmt = $this->db->prepare('UPDATE users SET reset_token = :token WHERE id = :id');
        $stmt->execute([
            ':token' => $token,
            ':id' => $userId
        ]);
    }

    /**
     * Получить пользователя по токену сброса пароля
     */
    public function getByResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE reset_token = :token');
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Обновить пароль пользователя
     */
    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->db->prepare('
            UPDATE users SET password_hash = :password, reset_token = NULL WHERE id = :id
        ');
        $stmt->execute([
            ':password' => $passwordHash,
            ':id' => $userId
        ]);
    }

    /**
     * Удалить токен сброса пароля
     */
    public function clearResetToken(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE users SET reset_token = NULL WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    /**
     * Получить всех менеджеров
     */
    public function getManagers(): array
    {
        $stmt = $this->db->prepare('SELECT id, username FROM users WHERE role = "manager"');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
