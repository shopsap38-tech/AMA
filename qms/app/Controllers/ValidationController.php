<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\ValidationService;
use RuntimeException;

/**
 * Traitement du circuit de validation (signatures électroniques).
 */
final class ValidationController extends Controller
{
    public function __construct(private readonly ValidationService $service)
    {
    }

    public function act(Request $request, int $ncId, int $stepId): never
    {
        $decision = (string) $request->input('decision');
        $signature = trim((string) $request->input('signature'));
        $comment = $request->input('comment') ?: null;

        if (!in_array($decision, ['approuve', 'rejete'], true)) {
            $this->withError('nonconformities/' . $ncId, 'Décision invalide.');
        }
        if ($signature === '') {
            $this->withError('nonconformities/' . $ncId, 'La signature électronique est obligatoire.');
        }

        try {
            $this->service->act($ncId, $stepId, $decision, $signature, $comment);
        } catch (RuntimeException $e) {
            $this->withError('nonconformities/' . $ncId, $e->getMessage());
        }

        $label = $decision === 'approuve' ? 'approuvée' : 'rejetée';
        $this->withSuccess('nonconformities/' . $ncId, "Étape {$label} et signée avec succès.");
    }
}
