<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repository des pièces jointes (photos, PDF, Excel, vidéos) avec gestion des
 * versions.
 */
final class AttachmentRepository extends BaseRepository
{
    protected string $table = 'attachments';

    protected array $fillable = [
        'attachable_type', 'attachable_id', 'original_name', 'stored_name',
        'mime_type', 'size', 'version', 'uploaded_by',
    ];

    /** @return array<int, array<string, mixed>> */
    public function forEntity(string $type, int $id): array
    {
        return $this->db->all(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS uploaded_by_name
             FROM attachments a
             LEFT JOIN users u ON u.id = a.uploaded_by
             WHERE a.attachable_type = ? AND a.attachable_id = ?
             ORDER BY a.original_name, a.version DESC",
            [$type, $id]
        );
    }

    /** Détermine le prochain numéro de version pour un fichier du même nom. */
    public function nextVersion(string $type, int $id, string $originalName): int
    {
        $max = $this->db->scalar(
            'SELECT MAX(version) FROM attachments
             WHERE attachable_type = ? AND attachable_id = ? AND original_name = ?',
            [$type, $id, $originalName]
        );
        return $max === null ? 1 : ((int) $max + 1);
    }
}
