<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Contrato para gerenciamento de sessão.
 * Qualquer classe que implementar esta interface deve fornecer os métodos abaixo.
 */
interface SessionInterface
{
    /**
     * Inicia a sessão com as opções fornecidas.
     *
     * @param array $options Opções de configuração (cookie_lifetime, cookie_secure, etc.)
     */
    public function start(array $options = []): void;

    /**
     * Define um valor na sessão.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void;

    /**
     * Obtém um valor da sessão.
     *
     * @param string $key
     * @param mixed $default Valor padrão se a chave não existir.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Verifica se uma chave existe na sessão.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remove um valor da sessão.
     *
     * @param string $key
     */
    public function delete(string $key): void;

    /**
     * Regenera o ID da sessão (segurança).
     */
    public function regenerate(): void;

    /**
     * Destroi completamente a sessão (logout).
     */
    public function destroy(): void;
}