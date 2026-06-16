<?php

declare(strict_types=1);

namespace App\Exception;

class HttpNotFoundException extends \Exception
{
    public function __construct(string $message = 'Página não encontrada', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}