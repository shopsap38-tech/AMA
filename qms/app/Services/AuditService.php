<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Request;
use App\Repositories\AuditRepository;

/**
 * Service de journalisation d'audit : enregistre qui a fait quoi, quand, avec
 * les valeurs avant/après, l'adresse IP et le navigateur.
 */
final class AuditService
{
    public function __construct(
        private readonly AuditRepository $repository,
        private readonly Auth $auth,
        private readonly Request $request,
    ) {
    }

    /**
     * @param array<string, mixed>|null $old
     * @param array<string, mixed>|null $new
     */
    public function log(string $action, string $entityType, ?int $entityId = null, ?array $old = null, ?array $new = null): void
    {
        $this->repository->create([
            'user_id'     => $this->auth->id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $old !== null ? json_encode($this->diffableValues($old), JSON_UNESCAPED_UNICODE) : null,
            'new_values'  => $new !== null ? json_encode($this->diffableValues($new), JSON_UNESCAPED_UNICODE) : null,
            'ip_address'  => $this->request->ip(),
            'user_agent'  => $this->request->userAgent(),
        ]);
    }

    /**
     * Retire les champs sensibles avant journalisation.
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function diffableValues(array $values): array
    {
        unset($values['password'], $values['password_hash'], $values['_token']);
        return $values;
    }
}
