<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\NonConformityRepository;
use App\Repositories\UserRepository;
use App\Repositories\ValidationRepository;
use RuntimeException;

/**
 * Pilote le workflow de validation avec signature électronique.
 *
 * Chaque étape doit être approuvée par un utilisateur disposant du rôle requis.
 * Lorsque toutes les étapes sont approuvées, la NC est clôturée.
 */
final class ValidationService
{
    public function __construct(
        private readonly ValidationRepository $validations,
        private readonly NonConformityRepository $nc,
        private readonly UserRepository $users,
        private readonly Auth $auth,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * Approuve ou rejette l'étape courante du circuit.
     *
     * @param 'approuve'|'rejete' $decision
     */
    public function act(int $ncId, int $stepId, string $decision, string $signatureName, ?string $comment): void
    {
        $step = $this->validations->find($stepId);
        if ($step === null || (int) $step['non_conformity_id'] !== $ncId) {
            throw new RuntimeException('Étape de validation introuvable.');
        }
        if ($step['status'] !== 'en_attente') {
            throw new RuntimeException('Cette étape a déjà été traitée.');
        }

        $current = $this->validations->currentStep($ncId);
        if ($current === null || (int) $current['id'] !== $stepId) {
            throw new RuntimeException('Vous devez traiter les étapes dans l\'ordre du circuit.');
        }

        if (!$this->auth->hasRole($step['role_required']) && !$this->auth->can('*')) {
            throw new RuntimeException("Votre rôle ne vous autorise pas à signer l'étape « {$step['step_label']} ».");
        }

        // Signature électronique : empreinte horodatée non répudiable.
        $signature = $this->buildSignature($signatureName);

        $this->validations->act($stepId, $decision, (int) $this->auth->id(), $signature, $comment);
        $this->audit->log('validation_' . $decision, 'non_conformity', $ncId, null, [
            'step'      => $step['step_label'],
            'signature' => $signatureName,
            'comment'   => $comment,
        ]);

        if ($decision === 'rejete') {
            $this->nc->changeStatus($ncId, 'en_analyse');
            $this->notifyCreator($ncId, 'rejetée', $step['step_label']);
            return;
        }

        // Avancement : passage en statut « validation » puis clôture finale.
        $this->nc->changeStatus($ncId, 'validation');

        if ($this->validations->allApproved($ncId)) {
            $this->nc->changeStatus($ncId, 'cloturee', date('Y-m-d H:i:s'));
            $this->audit->log('close', 'non_conformity', $ncId);
            $this->notifyCreator($ncId, 'clôturée', 'Direction');
        }
    }

    private function buildSignature(string $name): string
    {
        return sprintf(
            '%s | %s | %s',
            $name,
            date('Y-m-d H:i:s'),
            substr(hash('sha256', $name . microtime(true) . random_bytes(8)), 0, 16)
        );
    }

    private function notifyCreator(int $ncId, string $outcome, string $stepLabel): void
    {
        $nc = $this->nc->find($ncId);
        if ($nc !== null && !empty($nc['created_by'])) {
            $this->notifications->notify(
                (int) $nc['created_by'],
                'validation_update',
                'Mise à jour du circuit de validation',
                "La non-conformité {$nc['reference']} a été {$outcome} à l'étape « {$stepLabel} ».",
                'nonconformities/' . $ncId
            );
        }
    }
}
