<?php

declare(strict_types=1);

namespace App\Exception;

class ForbiddenException extends \Exception
{
    public function __construct(string $message = 'Acesso negado', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}