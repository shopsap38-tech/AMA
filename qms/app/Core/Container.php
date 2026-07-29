<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Conteneur d'injection de dépendances minimaliste (singleton + résolution
 * automatique). Permet de découpler contrôleurs, services et repositories.
 */
final class Container
{
    private static ?Container $instance = null;

    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function bind(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function singleton(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            return $this->instances[$id] = ($this->bindings[$id])($this);
        }

        return $this->instances[$id] = $this->build($id);
    }

    /**
     * Instancie une classe en résolvant récursivement ses dépendances typées.
     */
    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Service ou classe introuvable : {$class}");
        }

        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException(
                    "Impossible de résoudre le paramètre \${$parameter->getName()} de {$class}"
                );
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
