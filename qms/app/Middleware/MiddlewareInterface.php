<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

/**
 * Contrat commun aux middlewares. Un middleware retourne null pour laisser la
 * requête poursuivre, ou déclenche une réponse/redirection (jamais de retour).
 */
interface MiddlewareInterface
{
    /**
     * @param array<string, mixed> $params options éventuelles (ex. permission requise)
     */
    public function handle(Request $request, array $params = []): void;
}
