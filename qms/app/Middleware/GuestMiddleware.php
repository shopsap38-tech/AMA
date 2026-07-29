<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/**
 * Réservé aux visiteurs non connectés (pages de login) : redirige les
 * utilisateurs déjà authentifiés vers le tableau de bord.
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, array $params = []): void
    {
        if ($this->auth->check()) {
            Response::redirect('dashboard');
        }
    }
}
