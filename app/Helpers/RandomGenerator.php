<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Gerador de strings aleatórias para identificadores (hash_id, tokens, etc.).
 * 
 * Esta classe é responsável apenas por gerar strings alfanuméricas
 * de forma criptograficamente segura. A verificação de unicidade
 * deve ser feita pela camada de Service.
 */
class RandomGenerator
{
    /**
     * Gera uma string aleatória com caracteres alfanuméricos.
     *
     * @param int $length Comprimento da string gerada (padrão: 16)
     * @return string
     * @throws \InvalidArgumentException Se o comprimento for menor que 1
     */
    public static function generate(int $length = 16): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('O comprimento deve ser pelo menos 1.');
        }

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxIndex = strlen($characters) - 1;
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $maxIndex)];
        }

        return $randomString;
    }
}