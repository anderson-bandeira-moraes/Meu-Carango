<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Contracts\SessionInterface;

/**
 * Adaptador (wrapper) que implementa a SessionInterface
 * chamando os métodos estáticos da classe Session original.
 */
class SessionWrapper implements SessionInterface
{
    public function start(array $options = []): void
    {
        Session::start($options);
    }

    public function set(string $key, mixed $value): void
    {
        Session::set($key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Session::get($key, $default);
    }

    public function has(string $key): bool
    {
        return Session::has($key);
    }

    public function delete(string $key): void
    {
        Session::delete($key);
    }

    public function regenerate(): void
    {
        Session::regenerate();
    }

    public function destroy(): void
    {
        Session::destroy();
    }
}