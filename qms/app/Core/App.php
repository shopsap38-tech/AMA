<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RbacMiddleware;
use Throwable;

/**
 * Noyau de l'application : bootstrap, enregistrement des services, dispatch de
 * la requête à travers la pile de middlewares puis vers le contrôleur.
 */
final class App
{
    private Container $container;

    private Router $router;

    /** @var array<string, string> */
    private array $middlewareAliases = [
        'auth'  => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
        'csrf'  => CsrfMiddleware::class,
        'rbac'  => RbacMiddleware::class,
    ];

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';

        date_default_timezone_set($config['app']['timezone']);
        $this->configureErrorHandling((bool) $config['app']['debug']);

        $this->container = Container::getInstance();
        $this->registerCoreServices($config);

        Session::start();

        $this->router = new Router();
        $registerRoutes = require dirname(__DIR__, 2) . '/config/routes.php';
        $registerRoutes($this->router);
    }

    public function run(): void
    {
        $request = new Request();
        $this->container->singleton(Request::class, $request);

        try {
            $match = $this->router->match($request->method(), $request->uri());

            if ($match === null) {
                $this->renderError(404, 'Page introuvable');
                return;
            }

            foreach ($match['params'] as $key => $value) {
                $request->setAttribute($key, $value);
            }

            $this->runMiddleware($match['middleware'], $request);
            $this->dispatch($match['handler'], $match['params'], $request);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /** @param array<int, string> $middleware */
    private function runMiddleware(array $middleware, Request $request): void
    {
        foreach ($middleware as $definition) {
            [$alias, $param] = array_pad(explode(':', $definition, 2), 2, null);
            $class = $this->middlewareAliases[$alias] ?? null;
            if ($class === null) {
                continue;
            }

            /** @var \App\Middleware\MiddlewareInterface $instance */
            $instance = $this->container->get($class);
            $options = $param !== null ? ['permission' => $param] : [];
            $instance->handle($request, $options);
        }
    }

    /**
     * @param mixed                   $handler [Controller::class, 'method']
     * @param array<string, string>   $params
     */
    private function dispatch(mixed $handler, array $params, Request $request): void
    {
        [$controllerClass, $method] = $handler;
        $controller = $this->container->get($controllerClass);

        $arguments = $this->resolveMethodArguments($controller, $method, $params, $request);
        $result = $controller->$method(...$arguments);

        if (is_string($result)) {
            Response::html($result);
        }
    }

    /**
     * Injecte la Request et les paramètres d'URL dans la signature de l'action.
     *
     * @param array<string, string> $params
     * @return array<int, mixed>
     */
    private function resolveMethodArguments(object $controller, string $method, array $params, Request $request): array
    {
        $reflection = new \ReflectionMethod($controller, $method);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if ($type instanceof \ReflectionNamedType && $type->getName() === Request::class) {
                $arguments[] = $request;
            } elseif (array_key_exists($name, $params)) {
                $value = $params[$name];
                if ($type instanceof \ReflectionNamedType && $type->getName() === 'int') {
                    $value = (int) $value;
                }
                $arguments[] = $value;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                $arguments[] = null;
            }
        }

        return $arguments;
    }

    /** @param array<string, mixed> $config */
    private function registerCoreServices(array $config): void
    {
        $this->container->singleton(Database::class, new Database($config['database']));
    }

    private function configureErrorHandling(bool $debug): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
    }

    private function handleException(Throwable $e): void
    {
        error_log(sprintf('[QMS] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

        $request = $this->container->get(Request::class);
        if ($request instanceof Request && $request->wantsJson()) {
            Response::json([
                'message' => 'Erreur serveur.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        $this->renderError(500, 'Erreur interne du serveur', $e);
    }

    private function renderError(int $status, string $title, ?Throwable $e = null): void
    {
        http_response_code($status);
        $view = "errors.{$status}";
        try {
            echo View::render($view, ['title' => $title, 'exception' => $e]);
        } catch (Throwable) {
            echo "<h1>{$status} — {$title}</h1>";
            if ($e !== null && config('app.debug')) {
                echo '<pre>' . e($e->getMessage()) . "\n" . e($e->getTraceAsString()) . '</pre>';
            }
        }
    }
}
