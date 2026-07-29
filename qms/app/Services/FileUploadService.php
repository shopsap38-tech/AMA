<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AttachmentRepository;
use RuntimeException;

/**
 * Gère l'upload sécurisé de fichiers (photos, PDF, Excel, vidéo) avec contrôle
 * du type MIME réel, de la taille, et gestion des versions.
 */
final class FileUploadService
{
    public function __construct(private readonly AttachmentRepository $attachments)
    {
    }

    /**
     * Traite un tableau $_FILES multiple et persiste les métadonnées.
     *
     * @param array<string, mixed> $files structure $_FILES['attachments']
     * @return array<int, int> identifiants des pièces jointes créées
     */
    public function handleUpload(array $files, string $entityType, int $entityId, int $userId): array
    {
        $created = [];
        $normalized = $this->normalize($files);

        foreach ($normalized as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $id = $this->store($file, $entityType, $entityId, $userId);
            if ($id !== null) {
                $created[] = $id;
            }
        }

        return $created;
    }

    /**
     * @param array<string, mixed> $file
     */
    private function store(array $file, string $entityType, int $entityId, int $userId): ?int
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Erreur lors du téléversement : code {$file['error']}");
        }

        $maxSize = (int) config('uploads.max_size');
        if ($file['size'] > $maxSize) {
            throw new RuntimeException('Fichier trop volumineux (limite : ' . round($maxSize / 1048576) . ' Mo).');
        }

        $mime = $this->detectMime($file['tmp_name']);
        if (!in_array($mime, config('uploads.allowed_mimes', []), true)) {
            throw new RuntimeException("Type de fichier non autorisé : {$mime}");
        }

        $uploadPath = config('uploads.path');
        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0775, true) && !is_dir($uploadPath)) {
            throw new RuntimeException('Répertoire de téléversement inaccessible.');
        }

        $originalName = $this->sanitizeName($file['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = date('Ymd_His') . '_' . str_random(12) . ($extension !== '' ? '.' . $extension : '');
        $destination = rtrim($uploadPath, '/') . '/' . $storedName;

        if (!$this->moveFile($file['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible d\'enregistrer le fichier téléversé.');
        }

        $version = $this->attachments->nextVersion($entityType, $entityId, $originalName);

        return $this->attachments->create([
            'attachable_type' => $entityType,
            'attachable_id'   => $entityId,
            'original_name'   => $originalName,
            'stored_name'     => $storedName,
            'mime_type'       => $mime,
            'size'            => (int) $file['size'],
            'version'         => $version,
            'uploaded_by'     => $userId,
        ]);
    }

    private function moveFile(string $tmp, string $destination): bool
    {
        // move_uploaded_file en contexte web, copy en CLI/tests.
        if (is_uploaded_file($tmp)) {
            return move_uploaded_file($tmp, $destination);
        }
        return copy($tmp, $destination);
    }

    private function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path) ?: 'application/octet-stream';
    }

    private function sanitizeName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^\w.\- ]+/u', '_', $name) ?? 'fichier';
        return mb_substr($name, 0, 200);
    }

    /**
     * Transforme la structure $_FILES (mono ou multiple) en liste homogène.
     *
     * @param array<string, mixed> $files
     * @return array<int, array<string, mixed>>
     */
    private function normalize(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }

        if (!is_array($files['name'])) {
            return [$files];
        }

        $normalized = [];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $normalized[] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }
        return $normalized;
    }
}
