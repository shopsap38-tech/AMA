<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contrôleur de base : helpers de rendu, redirection et réponses JSON.
 */
abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = []): string
    {
        return View::render($view, $data);
    }

    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data = []): never
    {
        Response::html(View::render($view, $data));
    }

    protected function json(mixed $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }

    /**
     * Redirige en conservant les entrées et les erreurs (pattern « back »).
     *
     * @param array<string, mixed>                 $input
     * @param array<string, array<int, string>>    $errors
     */
    protected function back(string $path, array $errors = [], array $input = []): never
    {
        if ($errors !== []) {
            Session::flash('errors', $errors);
        }
        if ($input !== []) {
            Session::flash('_old', $input);
        }
        Response::redirect($path);
    }

    protected function withSuccess(string $path, string $message): never
    {
        Session::flash('success', $message);
        Response::redirect($path);
    }

    protected function withError(string $path, string $message): never
    {
        Session::flash('error', $message);
        Response::redirect($path);
    }
}
