<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected Database $db;

    public function __construct()
    {
        if ($this->table === '') {
            throw new \LogicException('Model must define $table property');
        }
        $this->db = Database::getInstance();
    }

    /**
     * Find a single record by primary key.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Return all records, optionally sorted.
     */
    public function all(string $orderBy = 'id ASC'): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Return all records matching a single column value.
     */
    public function where(string $column, mixed $value): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$column} = ?"
        );
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    /**
     * Return the first record matching a single column value.
     */
    public function findWhere(string $column, mixed $value): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Insert a new record and return its new primary key.
     */
    public function create(array $data): int
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing record by primary key.
     */
    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    /**
     * Delete a record by primary key.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * Count records matching an optional WHERE clause.
     */
    public function count(string $where = '1=1', array $params = []): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetch()['cnt'];
    }
}
