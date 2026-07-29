<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Refuse l'accès aux utilisateurs non authentifiés.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, array $params = []): void
    {
        if ($this->auth->check()) {
            return;
        }

        if ($request->wantsJson()) {
            Response::json(['message' => 'Non authentifié.'], 401);
        }

        Session::flash('error', 'Veuillez vous connecter pour accéder à cette page.');
        Session::flash('intended', $request->uri());
        Response::redirect('login');
    }
}
