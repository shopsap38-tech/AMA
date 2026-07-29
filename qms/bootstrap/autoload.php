<?php

/**
 * Autoloader PSR-4 autonome.
 *
 * Permet d'exécuter l'application sans `composer install` (utile sur Laragon /
 * WAMP). Si un autoloader Composer est présent il est chargé en priorité.
 */

declare(strict_types=1);

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . '/app/';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require $file;
        }
    });

    // Chargement des helpers globaux.
    require dirname(__DIR__) . '/app/Support/helpers.php';
}
