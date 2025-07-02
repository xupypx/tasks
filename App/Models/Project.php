<?php
namespace App\Models;

class Project {
    private $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM projects");
        return $stmt->fetchAll() ?? [];
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}