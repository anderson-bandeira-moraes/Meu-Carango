<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exceção lançada quando ocorre um erro de validação CSRF.
 * Token inválido, ausente ou expirado.
 */
class CsrfException extends ForbiddenException
{
    // Herda todo o comportamento de ForbiddenException.
    // Pode ser estendida futuramente com propriedades ou métodos específicos,
    // mas por enquanto é apenas para diferenciação de tipo.
}