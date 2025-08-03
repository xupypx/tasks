<?php

namespace App\Models;

use core\Model;
use PDO;
use PDOException;

class Setting extends Model
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM settings ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function get(string $name): ?string
    {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE name = :name");
        $stmt->execute(['name' => $name]);
        return $stmt->fetchColumn() ?: null;
    }

    public function updateSetting(string $name, string $value): void
    {
        $stmt = $this->db->prepare("UPDATE settings SET value = :value WHERE name = :name");
        $stmt->execute(['value' => $value, 'name' => $name]);
    }

    public function createSetting(string $title, string $name, string $value): void
    {
        $stmt = $this->db->prepare("INSERT INTO settings (title, name, value) VALUES (:title, :name, :value)");
        $stmt->execute(['title' => $title, 'name' => $name, 'value' => $value]);
    }

    public function deleteSetting(string $name): void
    {
        $stmt = $this->db->prepare("DELETE FROM settings WHERE name = :name");
        $stmt->execute(['name' => $name]);
    }
}
