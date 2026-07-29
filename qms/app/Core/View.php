<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Moteur de rendu de vues PHP natif avec support des layouts et des sections.
 */
final class View
{
    private static string $basePath = '';

    /** @var array<string, string> */
    private static array $sections = [];

    private static ?string $currentSection = null;

    private static ?string $layout = null;

    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): string
    {
        if (self::$basePath === '') {
            self::$basePath = dirname(__DIR__) . '/Views';
        }

        self::$layout = null;
        self::$sections = [];

        $content = self::renderFile($view, $data);

        if (self::$layout !== null) {
            // Si la vue n'a pas déclaré explicitement de section « content »,
            // on utilise sa sortie directe comme contenu principal du layout.
            if (!isset(self::$sections['content'])) {
                self::$sections['content'] = $content;
            }
            $content = self::renderFile(self::$layout, $data);
        }

        return $content;
    }

    /** @param array<string, mixed> $data */
    private static function renderFile(string $view, array $data): string
    {
        $file = self::$basePath . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Vue introuvable : {$view} ({$file})");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function extends(string $layout): void
    {
        self::$layout = $layout;
    }

    public static function section(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        if (self::$currentSection === null) {
            return;
        }
        self::$sections[self::$currentSection] = (string) ob_get_clean();
        self::$currentSection = null;
    }

    public static function yield(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    /** @param array<string, mixed> $data */
    public static function partial(string $view, array $data = []): string
    {
        return self::renderFile($view, $data);
    }
}
