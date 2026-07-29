<?php

/**
 * Configuration globale de l'application.
 *
 * Les valeurs peuvent être surchargées par des variables d'environnement afin
 * de ne jamais versionner d'identifiants sensibles en production.
 */

declare(strict_types=1);

$env = static fn (string $key, mixed $default = null): mixed => $_ENV[$key] ?? getenv($key) ?: $default;

return [
    'app' => [
        'name'     => 'Quality Management System',
        'short'    => 'QMS',
        'env'      => $env('APP_ENV', 'local'),
        'debug'    => filter_var($env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL),
        'url'      => rtrim((string) $env('APP_URL', ''), '/'),
        'locale'   => 'fr',
        'timezone' => 'Europe/Paris',
        'key'      => $env('APP_KEY', 'base64:qms-development-only-change-me'),
    ],

    'database' => [
        'host'     => $env('DB_HOST', '127.0.0.1'),
        'port'     => (int) $env('DB_PORT', '3306'),
        'name'     => $env('DB_NAME', 'qms'),
        'user'     => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
    ],

    'session' => [
        'name'     => 'QMS_SESSION',
        'lifetime' => 60 * 60 * 4, // 4 heures
    ],

    'uploads' => [
        'path'          => dirname(__DIR__) . '/public/uploads',
        'max_size'      => 25 * 1024 * 1024, // 25 Mo
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            'application/pdf',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'video/mp4', 'video/webm',
        ],
    ],

    'mail' => [
        'from'    => $env('MAIL_FROM', 'qualite@entreprise.com'),
        'name'    => $env('MAIL_FROM_NAME', 'QMS - Service Qualité'),
        'enabled' => filter_var($env('MAIL_ENABLED', 'false'), FILTER_VALIDATE_BOOL),
    ],
];
