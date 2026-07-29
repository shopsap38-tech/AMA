<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Contrôle d'accès basé sur les rôles (RBAC).
 *
 * La permission requise est passée via la syntaxe « rbac:nonconformity.create »
 * dans la définition de route.
 */
final class RbacMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, array $params = []): void
    {
        $permission = $params['permission'] ?? null;
        if ($permission === null || $this->auth->can($permission)) {
            return;
        }

        if ($request->wantsJson()) {
            Response::json(['message' => 'Accès refusé : permission insuffisante.'], 403);
        }

        Session::flash('error', "Vous n'avez pas les droits nécessaires pour cette action.");
        Response::html(
            \App\Core\View::render('errors.403', ['permission' => $permission]),
            403
        );
    }
}
