<?php

namespace App\Models;

use PDO;

class User
{
    private PDO $db;

    public int $id;
    public string $username;
    public string $email;
    public string $role;

    public function __construct(PDO $db = null)
    {
        // Подключаем БД, если не передана
        $this->db = $db ?? db();
    }

    public static function all(): array
    {
        $db = db();
        $stmt = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?self
    {
        $db = db();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::fromRow($row) : null;
    }


    public static function fromRow(array $row): self
    {
        $user = new self();
        $user->id = (int)$row['id'];
        $user->username = $row['username'];
        $user->email = $row['email'];
        $user->role = $row['role'];
        return $user;
    }

    public function save(): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id
        ');
        return $stmt->execute([
            ':username' => $this->username,
            ':email' => $this->email,
            ':role' => $this->role,
            ':id' => $this->id
        ]);
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, role)
            VALUES (:username, :email, :password_hash, :role)
        ");
        return $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role']
        ]);
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return (bool)$stmt->fetch();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return (bool)$stmt->fetch();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateResetToken(int $userId, string $token): void
    {
        $stmt = $this->db->prepare('UPDATE users SET reset_token = :token WHERE id = :id');
        $stmt->execute([
            ':token' => $token,
            ':id' => $userId
        ]);
    }

    public function getByResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE reset_token = :token');
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

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

    public function clearResetToken(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE users SET reset_token = NULL WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    public function getManagers(): array
    {
        $stmt = $this->db->prepare('SELECT id, username FROM users WHERE role = "manager"');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
