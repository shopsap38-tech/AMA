<?php
// Fonctions utilitaires : authentification, rôles, CSRF, affichage.

/** Échappe une chaîne pour l'affichage HTML. */
function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Redirige vers une URL de l'application (relative à BASE_URL). */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** L'utilisateur est-il connecté ? */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

/** Retourne l'utilisateur connecté (ou null). */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Retourne le rôle de l'utilisateur connecté (ou null). */
function current_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

/** Impose une authentification, sinon redirige vers la page de connexion. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/auth/login.php');
    }
}

/**
 * Impose que l'utilisateur possède l'un des rôles autorisés.
 * @param string[] $roles
 */
function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/header.php';
        echo '<div class="alert alert-danger">Accès refusé : vous n\'avez pas les droits nécessaires pour cette page.</div>';
        require __DIR__ . '/footer.php';
        exit;
    }
}

/** Libellé lisible d'un rôle. */
function role_label(?string $role): string
{
    return $GLOBALS['ROLES'][$role] ?? (string) $role;
}

/** Libellé lisible d'un statut. */
function statut_label(?string $statut): string
{
    return $GLOBALS['STATUTS'][$statut] ?? (string) $statut;
}

/** Classe de badge Bootstrap pour un statut. */
function statut_badge(?string $statut): string
{
    return $GLOBALS['STATUT_BADGE'][$statut] ?? 'secondary';
}

/** Jeton CSRF (généré une fois par session). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Champ caché contenant le jeton CSRF. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Vérifie le jeton CSRF d'une requête POST ; interrompt si invalide. */
function check_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        die('Jeton de sécurité invalide. Veuillez recharger la page et réessayer.');
    }
}

/** Message flash (une seule lecture). */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Récupère et vide les messages flash. */
function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Formate un délai en minutes de manière lisible (ex. 1 h 12). */
function format_delai(?int $minutes): string
{
    if ($minutes === null) {
        return '—';
    }
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $m === 0 ? "{$h} h" : "{$h} h {$m}";
}
