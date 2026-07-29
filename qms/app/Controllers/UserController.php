<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Repositories\DepartmentRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;

/**
 * Administration des utilisateurs et des rôles (RBAC).
 */
final class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly DepartmentRepository $departments,
        private readonly AuditService $audit,
    ) {
    }

    public function index(): string
    {
        return $this->view('users.index', [
            'users'  => $this->users->allWithRole(),
            'active' => 'users',
            'title'  => 'Utilisateurs',
        ]);
    }

    public function create(): string
    {
        return $this->view('users.form', [
            'user'        => null,
            'roles'       => $this->users->roles(),
            'departments' => $this->departments->options(),
            'active'      => 'users',
            'title'       => 'Nouvel utilisateur',
        ]);
    }

    public function store(Request $request): never
    {
        $data = $request->only(['first_name', 'last_name', 'email', 'password', 'role_id', 'department_id', 'job_title', 'phone']);

        $validator = new Validator($data, [
            'first_name' => 'required|max:100',
            'last_name'  => 'required|max:100',
            'email'      => 'required|email',
            'password'   => 'required|min:8',
            'role_id'    => 'required|integer',
        ], ['first_name' => 'Prénom', 'last_name' => 'Nom', 'email' => 'Email', 'password' => 'Mot de passe', 'role_id' => 'Rôle']);

        if ($validator->fails() || $this->users->emailExists((string) $data['email'])) {
            $errors = $validator->errors();
            if ($this->users->emailExists((string) $data['email'])) {
                $errors['email'][] = 'Cet email est déjà utilisé.';
            }
            $this->back('users/create', $errors, $data);
        }

        $id = $this->users->create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'role_id'       => (int) $data['role_id'],
            'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
            'job_title'     => $data['job_title'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'is_active'     => 1,
        ]);

        $this->audit->log('create', 'user', $id, null, ['email' => $data['email']]);
        $this->withSuccess('users', 'Utilisateur créé avec succès.');
    }

    public function edit(int $id): string
    {
        $user = $this->users->find($id);
        if ($user === null) {
            $this->withError('users', 'Utilisateur introuvable.');
        }

        return $this->view('users.form', [
            'user'        => $user,
            'roles'       => $this->users->roles(),
            'departments' => $this->departments->options(),
            'active'      => 'users',
            'title'       => 'Modifier l\'utilisateur',
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $data = $request->only(['first_name', 'last_name', 'email', 'password', 'role_id', 'department_id', 'job_title', 'phone', 'is_active']);

        $validator = new Validator($data, [
            'first_name' => 'required|max:100',
            'last_name'  => 'required|max:100',
            'email'      => 'required|email',
            'role_id'    => 'required|integer',
        ], ['first_name' => 'Prénom', 'last_name' => 'Nom', 'email' => 'Email', 'role_id' => 'Rôle']);

        if ($validator->fails() || $this->users->emailExists((string) $data['email'], $id)) {
            $errors = $validator->errors();
            if ($this->users->emailExists((string) $data['email'], $id)) {
                $errors['email'][] = 'Cet email est déjà utilisé.';
            }
            $this->back('users/' . $id . '/edit', $errors, $data);
        }

        $payload = [
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'role_id'       => (int) $data['role_id'],
            'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
            'job_title'     => $data['job_title'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'is_active'     => !empty($data['is_active']) ? 1 : 0,
        ];

        if (!empty($data['password'])) {
            $this->users->updatePassword($id, password_hash((string) $data['password'], PASSWORD_DEFAULT));
        }

        $this->users->update($id, $payload);
        $this->audit->log('update', 'user', $id, null, ['email' => $data['email']]);
        $this->withSuccess('users', 'Utilisateur mis à jour.');
    }
}
