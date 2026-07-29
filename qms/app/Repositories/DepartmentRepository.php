<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repository des services / départements.
 */
final class DepartmentRepository extends BaseRepository
{
    protected string $table = 'departments';

    protected array $fillable = ['name', 'code', 'manager_id'];

    /** @return array<int, array<string, mixed>> */
    public function options(): array
    {
        return $this->db->all('SELECT id, name FROM departments ORDER BY name');
    }
}
