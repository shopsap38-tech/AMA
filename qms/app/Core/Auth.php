<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

/**
 * Service d'authentification et point d'accès RBAC.
 *
 * Gère l'identité de l'utilisateur connecté, la vérification des mots de passe
 * (hachage Argon2id / bcrypt) et l'évaluation des permissions.
 */
final class Auth
{
    private const SESSION_KEY = 'auth_user_id';

    private ?array $user = null;

    /** @var array<int, string>|null */
    private ?array $permissions = null;

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || (int) $user['is_active'] !== 1) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Ré-hachage transparent si l'algorithme ou le coût a évolué.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $user['id']);
        $this->users->touchLastLogin((int) $user['id']);
        $this->user = $user;

        return true;
    }

    public function login(int $userId): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, $userId);
    }

    public function logout(): void
    {
        $this->user = null;
        $this->permissions = null;
        Session::remove(self::SESSION_KEY);
        Session::regenerate();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        $user = $this->user();
        return $user !== null ? (int) $user['id'] : null;
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = Session::get(self::SESSION_KEY);
        if ($id === null) {
            return null;
        }

        $this->user = $this->users->findWithRole((int) $id);
        return $this->user;
    }

    public function role(): ?string
    {
        return $this->user()['role_slug'] ?? null;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    /**
     * Vérifie qu'une permission est accordée au rôle de l'utilisateur.
     * La permission « * » accorde tous les droits (administrateur).
     */
    public function can(string $permission): bool
    {
        $permissions = $this->permissions();
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    /** @return array<int, string> */
    public function permissions(): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }

        $user = $this->user();
        if ($user === null) {
            return $this->permissions = [];
        }

        return $this->permissions = $this->users->permissionsForRole((int) $user['role_id']);
    }
}
