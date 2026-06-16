<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    /**
     * Inicia a sessão PHP com opções de segurança.
     *
     * @param array $options Opções de configuração da sessão.
     */
    public static function start(array $options = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Configurações padrão seguras
        $defaults = [
            'cookie_lifetime' => 0,
            'cookie_secure'   => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ];

        $settings = array_merge($defaults, $options);

        session_start($settings);
    }

    /**
     * Regenera o ID da sessão (proteção contra sequestro de sessão).
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Destroi completamente a sessão.
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Define um valor na sessão.
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Obtém um valor da sessão.
     */
    public static function get(string $key, $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica se a chave existe na sessão.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove um valor da sessão.
     */
    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Retorna todos os dados da sessão.
     */
    public static function all(): array
    {
        return $_SESSION;
    }

    /**
     * Limpa todos os dados da sessão (mantém a sessão ativa).
     */
    public static function flush(): void
    {
        $_SESSION = [];
    }
}