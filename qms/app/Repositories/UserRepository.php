<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Accès aux utilisateurs, rôles et permissions (RBAC).
 */
final class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    protected array $fillable = [
        'role_id', 'first_name', 'last_name', 'email',
        'password_hash', 'department_id', 'job_title', 'phone', 'is_active',
    ];

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return $this->db->first('SELECT * FROM users WHERE email = ?', [$email]);
    }

    /** @return array<string, mixed>|null */
    public function findWithRole(int $id): ?array
    {
        return $this->db->first(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, d.name AS department_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.id = ?',
            [$id]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function allWithRole(): array
    {
        return $this->db->all(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug, d.name AS department_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN departments d ON d.id = u.department_id
             ORDER BY u.last_name, u.first_name'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function activeUsers(): array
    {
        return $this->db->all(
            "SELECT id, first_name, last_name, email FROM users
             WHERE is_active = 1 ORDER BY last_name, first_name"
        );
    }

    /** @return array<int, string> */
    public function permissionsForRole(int $roleId): array
    {
        $rows = $this->db->all(
            'SELECT p.slug FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?',
            [$roleId]
        );
        return array_column($rows, 'slug');
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->db->query('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [$hash, $id]);
    }

    public function touchLastLogin(int $id): void
    {
        $this->db->query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function roles(): array
    {
        return $this->db->all('SELECT * FROM roles ORDER BY id');
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            return (int) $this->db->scalar(
                'SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
                [$email, $exceptId]
            ) > 0;
        }
        return (int) $this->db->scalar('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0;
    }
}
