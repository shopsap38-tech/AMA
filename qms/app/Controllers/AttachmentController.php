<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AttachmentRepository;
use App\Services\AuditService;
use App\Services\FileUploadService;
use RuntimeException;

/**
 * Upload, téléchargement et suppression des pièces jointes.
 */
final class AttachmentController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploader,
        private readonly AttachmentRepository $repository,
        private readonly AuditService $audit,
    ) {
    }

    public function store(Request $request, int $ncId): never
    {
        $files = $request->file('attachments');
        if ($files === null) {
            $this->withError('nonconformities/' . $ncId, 'Aucun fichier sélectionné.');
        }

        try {
            $ids = $this->uploader->handleUpload($files, 'non_conformity', $ncId, (int) auth()->id());
        } catch (RuntimeException $e) {
            $this->withError('nonconformities/' . $ncId, $e->getMessage());
        }

        $this->audit->log('upload', 'non_conformity', $ncId, null, ['count' => count($ids)]);
        $this->withSuccess('nonconformities/' . $ncId, count($ids) . ' fichier(s) téléversé(s).');
    }

    public function download(int $id): never
    {
        $attachment = $this->repository->find($id);
        if ($attachment === null) {
            Response::html('<h1>404 — Fichier introuvable</h1>', 404);
        }

        $path = rtrim(config('uploads.path'), '/') . '/' . $attachment['stored_name'];
        if (!is_file($path)) {
            Response::html('<h1>404 — Fichier absent du disque</h1>', 404);
        }

        Response::download($path, $attachment['original_name'], $attachment['mime_type']);
    }

    public function destroy(int $ncId, int $id): never
    {
        $attachment = $this->repository->find($id);
        if ($attachment !== null) {
            $path = rtrim(config('uploads.path'), '/') . '/' . $attachment['stored_name'];
            if (is_file($path)) {
                @unlink($path);
            }
            $this->repository->delete($id);
            $this->audit->log('delete', 'attachment', $id, $attachment, null);
        }
        $this->withSuccess('nonconformities/' . $ncId, 'Pièce jointe supprimée.');
    }
}
