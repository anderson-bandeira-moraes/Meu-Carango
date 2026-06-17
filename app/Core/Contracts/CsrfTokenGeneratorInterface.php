<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Contrato para geradores de token CSRF.
 * Qualquer classe que implementar esta interface deve fornecer um método
 * para gerar tokens criptograficamente seguros.
 */
interface CsrfTokenGeneratorInterface
{
    /**
     * Gera um token CSRF criptograficamente seguro.
     *
     * @return string Token gerado (ex: string hexadecimal de 64 caracteres).
     * @throws \RuntimeException Se a geração falhar (ex: falta de entropia).
     */
    public function generate(): string;
}