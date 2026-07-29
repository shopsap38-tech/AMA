<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Repository de base implémentant les opérations CRUD génériques.
 * Chaque repository concret définit sa table et ses colonnes assignables.
 */
abstract class BaseRepository
{
    protected string $table = '';

    /** @var array<int, string> Colonnes autorisées en écriture (protection mass-assignment). */
    protected array $fillable = [];

    public function __construct(protected readonly Database $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->all("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
    }

    public function count(): int
    {
        return (int) $this->db->scalar("SELECT COUNT(*) FROM {$this->table}");
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $data = $this->onlyFillable($data);
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        $this->db->query($sql, $this->bindings($data));
        return $this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        $data = $this->onlyFillable($data);
        if ($data === []) {
            return false;
        }

        $assignments = array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($data));
        $sql = sprintf('UPDATE %s SET %s WHERE id = :id', $this->table, implode(', ', $assignments));

        $bindings = $this->bindings($data);
        $bindings[':id'] = $id;

        return $this->db->query($sql, $bindings)->rowCount() >= 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id])->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function onlyFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function bindings(array $data): array
    {
        $bindings = [];
        foreach ($data as $key => $value) {
            $bindings[':' . $key] = $value;
        }
        return $bindings;
    }
}
