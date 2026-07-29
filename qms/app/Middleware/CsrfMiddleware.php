<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Vérifie le jeton CSRF sur toutes les requêtes mutantes (POST/PUT/PATCH/DELETE).
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, array $params = []): void
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = $request->input('_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        if (!Csrf::verify($token)) {
            if ($request->wantsJson()) {
                Response::json(['message' => 'Jeton CSRF invalide ou expiré.'], 419);
            }
            Session::flash('error', 'La session a expiré, veuillez réessayer.');
            Response::redirect(ltrim($request->uri(), '/'));
        }
    }
}
