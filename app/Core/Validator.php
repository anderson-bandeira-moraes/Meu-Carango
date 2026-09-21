<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use PDO;

/**
 * Serviço de validação que depende do banco de dados.
 *
 * Fornece a regra `exists` para o FormRequest, sem que o FormRequest
 * conheça diretamente o PDO.
 */
class Validator
{
    public function __construct(private PDO $pdo) {}

    /**
     * Verifica se o valor existe na tabela/coluna informadas.
     *
     * @param string $table  Nome da tabela (allowlist: letras, números, underscore).
     * @param string $column Nome da coluna (mesma allowlist).
     * @param mixed  $value  Valor a verificar.
     * @return bool
     * @throws InvalidArgumentException Se table ou column tiver formato inválido.
     */
    public function exists(string $table, string $column, mixed $value): bool
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new InvalidArgumentException("Nome de tabela inválido: {$table}");
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new InvalidArgumentException("Nome de coluna inválido: {$column}");
        }

        $stmt = $this->pdo->prepare("SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
        $stmt->execute([$value]);

        return (bool) $stmt->fetchColumn();
    }
}