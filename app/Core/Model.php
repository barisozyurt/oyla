<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected Database $db;

    /** Whitelist for orderBy column names — alt sınıflar override eder. */
    protected array $sortable = ['id', 'name', 'created_at'];

    public function __construct()
    {
        if ($this->table === '') {
            throw new \LogicException('Model must define $table property');
        }
        $this->db = Database::getInstance();
    }

    /** Database singleton'a kontrollü erişim (Repository pattern öncesi köprü). */
    public function db(): Database
    {
        return $this->db;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Tüm kayıtları döndürür. ORDER BY column whitelist ile kontrol edilir.
     * Eski kullanım `$model->all('id DESC')` korunur ama büyük tablolar için
     * paginate() tercih edilmelidir.
     */
    public function all(string $orderBy = 'id ASC'): array
    {
        $order = $this->safeOrderBy($orderBy);
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY {$order}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Sayfalı listeleme.
     *
     * @return array{rows:array<int,array>, total:int, page:int, perPage:int, lastPage:int}
     */
    public function paginate(int $page = 1, int $perPage = 50, string $orderBy = 'id DESC', string $whereSql = '1=1', array $whereParams = []): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(500, $perPage));
        $offset  = ($page - 1) * $perPage;
        $order   = $this->safeOrderBy($orderBy);

        $countStmt = $this->db->prepare("SELECT COUNT(*) c FROM {$this->table} WHERE {$whereSql}");
        $countStmt->execute($whereParams);
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$whereSql} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($whereParams);
        $rows = $stmt->fetchAll();

        return [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    public function where(string $column, mixed $value): array
    {
        $col = $this->safeColumn($column);
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$col} = ?"
        );
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public function findWhere(string $column, mixed $value): ?array
    {
        $col = $this->safeColumn($column);
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$col} = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $columns      = implode(', ', array_map([$this, 'safeColumn'], array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($col) => $this->safeColumn($col) . ' = ?', array_keys($data)));
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([$id]);
    }

    public function count(string $where = '1=1', array $params = []): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetch()['cnt'];
    }

    /** Kolon ismi: sadece alfanümerik + altçizgi. Aksi halde "id" döndür. */
    protected function safeColumn(string $name): string
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $name) ? $name : 'id';
    }

    /**
     * ORDER BY ifadesini güvenli hale getirir: "col1 ASC, col2 DESC" formatı,
     * sadece $sortable listesinde olanlar kabul edilir.
     */
    protected function safeOrderBy(string $orderBy): string
    {
        $clauses = [];
        foreach (explode(',', $orderBy) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            // "col DIR" parça parça
            $tokens = preg_split('/\s+/', $part);
            $col = $this->safeColumn($tokens[0] ?? 'id');
            $dir = strtoupper($tokens[1] ?? 'ASC');
            if (!in_array($dir, ['ASC', 'DESC'], true)) {
                $dir = 'ASC';
            }
            if ($this->sortable && !in_array($col, $this->sortable, true)) {
                // sortable whitelist varsa ve col yoksa, primary key'e düş
                $col = $this->primaryKey;
            }
            $clauses[] = "{$col} {$dir}";
        }
        return $clauses ? implode(', ', $clauses) : "{$this->primaryKey} ASC";
    }
}
