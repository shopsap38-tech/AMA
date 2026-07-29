<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuditService;

/**
 * Authentification : affichage du formulaire, connexion, déconnexion.
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly Auth $auth,
        private readonly AuditService $audit,
    ) {
    }

    public function showLogin(): string
    {
        return $this->view('auth.login');
    }

    public function login(Request $request): never
    {
        $data = $request->only(['email', 'password']);

        $validator = new Validator($data, [
            'email'    => 'required|email',
            'password' => 'required',
        ], ['email' => 'Email', 'password' => 'Mot de passe']);

        if ($validator->fails()) {
            $this->back('login', $validator->errors(), ['email' => $data['email'] ?? '']);
        }

        if (!$this->auth->attempt((string) $data['email'], (string) $data['password'])) {
            $this->audit->log('login_failed', 'auth', null, null, ['email' => $data['email']]);
            $this->back('login', ['email' => ['Identifiants incorrects ou compte désactivé.']], ['email' => $data['email']]);
        }

        $this->audit->log('login', 'auth', $this->auth->id());

        $intended = Session::flash('intended');
        $this->withSuccess(
            is_string($intended) ? ltrim($intended, '/') : 'dashboard',
            'Bienvenue ' . ($this->auth->user()['first_name'] ?? '') . ' !'
        );
    }

    public function logout(): never
    {
        $this->audit->log('logout', 'auth', $this->auth->id());
        $this->auth->logout();
        $this->withSuccess('login', 'Vous avez été déconnecté.');
    }
}
