<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repository du centre de notifications.
 */
final class NotificationRepository extends BaseRepository
{
    protected string $table = 'notifications';

    protected array $fillable = [
        'user_id', 'type', 'title', 'message', 'link', 'is_read',
    ];

    /** @return array<int, array<string, mixed>> */
    public function forUser(int $userId, int $limit = 20): array
    {
        return $this->db->all(
            "SELECT * FROM notifications WHERE user_id = ?
             ORDER BY created_at DESC LIMIT {$limit}",
            [$userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
    }

    public function markAsRead(int $id, int $userId): void
    {
        $this->db->query(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function markAllAsRead(int $userId): void
    {
        $this->db->query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$userId]);
    }
}
