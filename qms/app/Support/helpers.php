<?php

/**
 * Fonctions utilitaires globales.
 */

declare(strict_types=1);

use App\Core\Container;
use App\Core\Session;

if (!function_exists('config')) {
    /**
     * Récupère une valeur de configuration via notation pointée : config('app.name').
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
        }

        $segments = explode('.', $key);
        $value = $config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('app')) {
    /**
     * Résout un service depuis le conteneur d'injection de dépendances.
     */
    function app(?string $id = null): mixed
    {
        $container = Container::getInstance();
        return $id === null ? $container : $container->get($id);
    }
}

if (!function_exists('e')) {
    /**
     * Échappe une chaîne pour un affichage HTML sûr (protection XSS).
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('old')) {
    /**
     * Récupère une ancienne valeur de formulaire après échec de validation.
     */
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::flash('_old') ?? [];
        return $old[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = config('app.url', '');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('auth')) {
    function auth(): \App\Core\Auth
    {
        return app(\App\Core\Auth::class);
    }
}

if (!function_exists('str_random')) {
    function str_random(int $length = 32): string
    {
        return bin2hex(random_bytes(max(1, (int) ceil($length / 2))));
    }
}

if (!function_exists('errors_for')) {
    /**
     * Retourne les messages d'erreur de validation pour un champ donné.
     *
     * @return array<int, string>
     */
    function errors_for(string $field): array
    {
        $errors = Session::flash('errors') ?? [];
        return $errors[$field] ?? [];
    }
}

if (!function_exists('ui_label')) {
    /** Libellé lisible d'une valeur d'énumération métier. */
    function ui_label(string $group, ?string $key): string
    {
        static $maps = [
            'severity' => [
                'critique' => 'Critique', 'majeure' => 'Majeure', 'mineure' => 'Mineure',
            ],
            'status' => [
                'ouverte' => 'Ouverte', 'en_analyse' => 'En analyse',
                'action_corrective' => 'Action corrective', 'validation' => 'Validation',
                'cloturee' => 'Clôturée',
            ],
            'origin' => [
                'production' => 'Production', 'stock' => 'Stock', 'reception' => 'Réception',
                'expedition' => 'Expédition', 'client' => 'Client', 'fournisseur' => 'Fournisseur',
            ],
            'priority' => [
                'basse' => 'Basse', 'normale' => 'Normale', 'haute' => 'Haute', 'urgente' => 'Urgente',
            ],
            'action_status' => [
                'a_faire' => 'À faire', 'en_cours' => 'En cours',
                'terminee' => 'Terminée', 'annulee' => 'Annulée',
            ],
            'step_status' => [
                'en_attente' => 'En attente', 'approuve' => 'Approuvé', 'rejete' => 'Rejeté',
            ],
        ];
        return $maps[$group][$key] ?? (string) ($key ?? '—');
    }
}

if (!function_exists('ui_badge')) {
    /** Classe CSS de badge associée à une valeur d'énumération. */
    function ui_badge(string $group, ?string $key): string
    {
        static $maps = [
            'severity' => [
                'critique' => 'badge-critique', 'majeure' => 'badge-majeure', 'mineure' => 'badge-mineure',
            ],
            'status' => [
                'ouverte' => 'badge-open', 'en_analyse' => 'badge-analysis',
                'action_corrective' => 'badge-action', 'validation' => 'badge-validation',
                'cloturee' => 'badge-closed',
            ],
            'priority' => [
                'basse' => 'badge-mineure', 'normale' => 'badge-info',
                'haute' => 'badge-majeure', 'urgente' => 'badge-critique',
            ],
            'action_status' => [
                'a_faire' => 'badge-open', 'en_cours' => 'badge-action',
                'terminee' => 'badge-closed', 'annulee' => 'badge-muted',
            ],
            'step_status' => [
                'en_attente' => 'badge-info', 'approuve' => 'badge-closed', 'rejete' => 'badge-critique',
            ],
        ];
        return $maps[$group][$key] ?? 'badge-muted';
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $value, string $format = 'd/m/Y'): string
    {
        if (empty($value)) {
            return '—';
        }
        $ts = strtotime($value);
        return $ts === false ? (string) $value : date($format, $ts);
    }
}
