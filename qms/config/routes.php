<?php

/**
 * Définition des routes de l'application.
 *
 * Les middlewares sont appliqués via alias : auth, guest, csrf, rbac:<permission>.
 */

declare(strict_types=1);

use App\Controllers\AttachmentController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\CorrectiveActionController;
use App\Controllers\DashboardController;
use App\Controllers\NonConformityController;
use App\Controllers\NotificationController;
use App\Controllers\ReportController;
use App\Controllers\UserController;
use App\Controllers\ValidationController;
use App\Core\Router;

return static function (Router $router): void {

    // --- Accueil : redirige vers le tableau de bord --------------------------
    $router->get('/', [DashboardController::class, 'index'], ['auth']);

    // --- Authentification ----------------------------------------------------
    $router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
    $router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf']);
    $router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);

    // --- Tableau de bord -----------------------------------------------------
    $router->get('/dashboard', [DashboardController::class, 'index'], ['auth']);
    $router->get('/api/dashboard', [DashboardController::class, 'data'], ['auth']);

    // --- Non-conformités -----------------------------------------------------
    $router->group(['prefix' => '/nonconformities', 'middleware' => ['auth']], static function (Router $r): void {
        $r->get('', [NonConformityController::class, 'index'], ['rbac:nonconformity.view']);
        $r->get('/create', [NonConformityController::class, 'create'], ['rbac:nonconformity.create']);
        $r->post('', [NonConformityController::class, 'store'], ['csrf', 'rbac:nonconformity.create']);
        $r->get('/{id}', [NonConformityController::class, 'show'], ['rbac:nonconformity.view']);
        $r->get('/{id}/edit', [NonConformityController::class, 'edit'], ['rbac:nonconformity.update']);
        $r->put('/{id}', [NonConformityController::class, 'update'], ['csrf', 'rbac:nonconformity.update']);
        $r->delete('/{id}', [NonConformityController::class, 'destroy'], ['csrf', 'rbac:nonconformity.delete']);

        // Actions correctives (imbriquées)
        $r->post('/{ncId}/actions', [CorrectiveActionController::class, 'store'], ['csrf', 'rbac:action.create']);
        $r->put('/{ncId}/actions/{id}', [CorrectiveActionController::class, 'update'], ['csrf', 'rbac:action.update']);
        $r->post('/{ncId}/actions/{id}/comments', [CorrectiveActionController::class, 'comment'], ['csrf', 'rbac:action.update']);
        $r->delete('/{ncId}/actions/{id}', [CorrectiveActionController::class, 'destroy'], ['csrf', 'rbac:action.delete']);

        // Validation (workflow)
        $r->post('/{ncId}/validations/{stepId}', [ValidationController::class, 'act'], ['csrf', 'rbac:validation.act']);

        // Pièces jointes
        $r->post('/{ncId}/attachments', [AttachmentController::class, 'store'], ['csrf', 'rbac:nonconformity.update']);
        $r->delete('/{ncId}/attachments/{id}', [AttachmentController::class, 'destroy'], ['csrf', 'rbac:nonconformity.update']);
    });

    // --- API REST ------------------------------------------------------------
    $router->get('/api/nonconformities', [NonConformityController::class, 'apiSearch'], ['auth', 'rbac:nonconformity.view']);

    // --- Pièces jointes : téléchargement -------------------------------------
    $router->get('/attachments/{id}/download', [AttachmentController::class, 'download'], ['auth']);

    // --- Notifications -------------------------------------------------------
    $router->get('/notifications', [NotificationController::class, 'index'], ['auth']);
    $router->get('/api/notifications', [NotificationController::class, 'feed'], ['auth']);
    $router->post('/notifications/read-all', [NotificationController::class, 'readAll'], ['auth', 'csrf']);
    $router->post('/notifications/{id}/read', [NotificationController::class, 'read'], ['auth', 'csrf']);

    // --- Rapports ------------------------------------------------------------
    $router->group(['prefix' => '/reports', 'middleware' => ['auth', 'rbac:report.view']], static function (Router $r): void {
        $r->get('', [ReportController::class, 'index']);
        $r->get('/csv', [ReportController::class, 'csv']);
        $r->get('/excel', [ReportController::class, 'excel']);
        $r->get('/print', [ReportController::class, 'print']);
    });

    // --- Journal d'audit -----------------------------------------------------
    $router->get('/audit', [AuditController::class, 'index'], ['auth', 'rbac:audit.view']);

    // --- Administration des utilisateurs -------------------------------------
    $router->group(['prefix' => '/users', 'middleware' => ['auth', 'rbac:user.manage']], static function (Router $r): void {
        $r->get('', [UserController::class, 'index']);
        $r->get('/create', [UserController::class, 'create']);
        $r->post('', [UserController::class, 'store'], ['csrf']);
        $r->get('/{id}/edit', [UserController::class, 'edit']);
        $r->put('/{id}', [UserController::class, 'update'], ['csrf']);
    });
};
