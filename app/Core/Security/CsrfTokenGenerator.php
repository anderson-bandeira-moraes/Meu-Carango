<?php

declare(strict_types=1);

namespace App\Core\Security;

use App\Core\Contracts\CsrfTokenGeneratorInterface;

class CsrfTokenGenerator implements CsrfTokenGeneratorInterface
{
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}