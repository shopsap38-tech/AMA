<?php

/**
 * Fonctions utilitaires communes (PHP natif) : session, sécurité (CSRF/XSS),
 * authentification, RBAC, journal d'audit, notifications et libellés.
 *
 * Inclure ce fichier en tête de chaque page : require 'includes/functions.php';
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Paris');

require __DIR__ . '/../config/database.php'; // fournit $pdo

// --- Session --------------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('QMS_SESSION');
    session_set_cookie_params([
        'lifetime' => 14400,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// =========================================================================
//  Sécurité : échappement XSS
// =========================================================================
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// =========================================================================
//  Sécurité : CSRF
// =========================================================================
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** Vérifie le jeton CSRF ; interrompt la requête si invalide. */
function csrf_check(): void
{
    $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
        http_response_code(419);
        die('Jeton CSRF invalide ou expiré. Veuillez revenir en arrière et réessayer.');
    }
}

// =========================================================================
//  Messages flash
// =========================================================================
function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    if (!empty($_SESSION['_flash'][$type])) {
        $msg = $_SESSION['_flash'][$type];
        unset($_SESSION['_flash'][$type]);
        return $msg;
    }
    return null;
}

/** Redirige puis stoppe le script. */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

// =========================================================================
//  Authentification & RBAC
// =========================================================================
function current_user(): ?array
{
    global $pdo;
    static $cache = null;
    if ($cache !== null) {
        return $cache ?: null;
    }
    if (empty($_SESSION['user_id'])) {
        $cache = false;
        return null;
    }
    $stmt = $pdo->prepare(
        'SELECT u.*, r.slug AS role_slug, r.name AS role_name, d.name AS department_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $cache = $stmt->fetch() ?: false;
    return $cache ?: null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

/** Force l'authentification ; redirige vers login si absent. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Veuillez vous connecter pour continuer.');
        redirect('login.php');
    }
}

/** @return array<int,string> Permissions du rôle de l'utilisateur connecté. */
function user_permissions(): array
{
    global $pdo;
    static $perms = null;
    if ($perms !== null) {
        return $perms;
    }
    $user = current_user();
    if ($user === null) {
        return $perms = [];
    }
    $stmt = $pdo->prepare(
        'SELECT p.slug FROM permissions p
         INNER JOIN role_permissions rp ON rp.permission_id = p.id
         WHERE rp.role_id = ?'
    );
    $stmt->execute([$user['role_id']]);
    return $perms = array_column($stmt->fetchAll(), 'slug');
}

function can(string $permission): bool
{
    $perms = user_permissions();
    return in_array('*', $perms, true) || in_array($permission, $perms, true);
}

/** Exige une permission ; affiche 403 sinon. */
function require_permission(string $permission): void
{
    require_login();
    if (!can($permission)) {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

function has_role(string ...$roles): bool
{
    $user = current_user();
    return $user !== null && in_array($user['role_slug'], $roles, true);
}

// =========================================================================
//  Journal d'audit
// =========================================================================
function audit_log(string $action, string $entityType, ?int $entityId = null, ?array $old = null, ?array $new = null): void
{
    global $pdo;
    $strip = static function (?array $a): ?string {
        if ($a === null) {
            return null;
        }
        unset($a['password'], $a['password_hash'], $a['_token']);
        return json_encode($a, JSON_UNESCAPED_UNICODE);
    };
    $user = current_user();
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $user['id'] ?? null,
        $action,
        $entityType,
        $entityId,
        $strip($old),
        $strip($new),
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
    ]);
}

// =========================================================================
//  Notifications
// =========================================================================
function notify(int $userId, string $type, string $title, string $message, ?string $link = null): void
{
    global $pdo;
    if ($userId <= 0) {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at)
         VALUES (?, ?, ?, ?, ?, 0, NOW())'
    );
    $stmt->execute([$userId, $type, $title, $message, $link]);
}

function unread_notifications_count(): int
{
    global $pdo;
    $user = current_user();
    if ($user === null) {
        return 0;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user['id']]);
    return (int) $stmt->fetchColumn();
}

// =========================================================================
//  Libellés & badges métier
// =========================================================================
function ui_label(string $group, ?string $key): string
{
    static $maps = [
        'severity'      => ['critique' => 'Critique', 'majeure' => 'Majeure', 'mineure' => 'Mineure'],
        'status'        => ['ouverte' => 'Ouverte', 'en_analyse' => 'En analyse', 'action_corrective' => 'Action corrective', 'validation' => 'Validation', 'cloturee' => 'Clôturée'],
        'origin'        => ['production' => 'Production', 'stock' => 'Stock', 'reception' => 'Réception', 'expedition' => 'Expédition', 'client' => 'Client', 'fournisseur' => 'Fournisseur'],
        'priority'      => ['basse' => 'Basse', 'normale' => 'Normale', 'haute' => 'Haute', 'urgente' => 'Urgente'],
        'action_status' => ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'],
        'step_status'   => ['en_attente' => 'En attente', 'approuve' => 'Approuvé', 'rejete' => 'Rejeté'],
        'role'          => ['admin' => 'Administrateur', 'direction' => 'Direction', 'responsable_qualite' => 'Responsable Qualité', 'responsable_production' => 'Responsable Production', 'chef_equipe' => "Chef d'équipe", 'employe' => 'Employé'],
    ];
    return $maps[$group][$key] ?? (string) ($key ?? '—');
}

function ui_badge(string $group, ?string $key): string
{
    static $maps = [
        'severity'      => ['critique' => 'badge-critique', 'majeure' => 'badge-majeure', 'mineure' => 'badge-mineure'],
        'status'        => ['ouverte' => 'badge-open', 'en_analyse' => 'badge-analysis', 'action_corrective' => 'badge-action', 'validation' => 'badge-validation', 'cloturee' => 'badge-closed'],
        'priority'      => ['basse' => 'badge-mineure', 'normale' => 'badge-info', 'haute' => 'badge-majeure', 'urgente' => 'badge-critique'],
        'action_status' => ['a_faire' => 'badge-open', 'en_cours' => 'badge-action', 'terminee' => 'badge-closed', 'annulee' => 'badge-muted'],
        'step_status'   => ['en_attente' => 'badge-info', 'approuve' => 'badge-closed', 'rejete' => 'badge-critique'],
    ];
    return $maps[$group][$key] ?? 'badge-muted';
}

function format_date(?string $value, string $format = 'd/m/Y'): string
{
    if (empty($value)) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts === false ? (string) $value : date($format, $ts);
}

/** Génère le prochain numéro NC-AAAA-000001. */
function next_nc_reference(int $year): string
{
    global $pdo;
    $prefix = "NC-{$year}-";
    $stmt = $pdo->prepare('SELECT reference FROM non_conformities WHERE reference LIKE ? ORDER BY reference DESC LIMIT 1');
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $seq = is_string($last) ? ((int) substr($last, strlen($prefix)) + 1) : 1;
    return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
}
