<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class Container
{
    /**
     * Armazena as fábricas (callbacks) e instâncias resolvidas.
     * @var array<string, object|callable>
     */
    private array $bindings = [];

    /**
     * Registra uma fábrica para um ID.
     *
     * @param string   $id      Identificador (geralmente o nome da classe/interface).
     * @param callable $factory Função que recebe o próprio Container e retorna a instância.
     */
    public function set(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    /**
     * Obtém uma instância registrada. Cria apenas uma vez (singleton).
     *
     * @param string $id
     * @return mixed
     * @throws RuntimeException se o ID não estiver registrado.
     */
    public function get(string $id): mixed
    {
        if (!array_key_exists($id, $this->bindings)) {
            throw new RuntimeException("Dependência não registrada: {$id}");
        }

        // Se já foi resolvido e é um objeto, retorna a instância única.
        if (is_object($this->bindings[$id]) && !$this->bindings[$id] instanceof \Closure) {
            return $this->bindings[$id];
        }

        // Cria a instância e armazena para próximas chamadas (singleton).
        $instance = $this->bindings[$id]($this);
        $this->bindings[$id] = $instance;

        return $instance;
    }
}