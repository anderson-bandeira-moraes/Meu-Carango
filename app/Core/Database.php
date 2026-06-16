<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    /**
     * Inicializa a conexão com o banco de dados.
     *
     * @param array $config Chaves: host, name, user, pass, charset, port (opcional)
     */
    public static function init(array $config): void
    {
        if (self::$pdo !== null) {
            return;
        }

        $host    = $config['host']    ?? 'localhost';
        $name    = $config['name']    ?? '';
        $user    = $config['user']    ?? 'root';
        $pass    = $config['pass']    ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $port    = $config['port']    ?? '3306';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lança exceções em erros SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retorna arrays associativos por padrão
            PDO::ATTR_EMULATE_PREPARES   => false,                   // Usa prepared statements nativos
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    /**
     * Retorna a instância PDO da conexão.
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Banco de dados não inicializado. Chame Database::init() primeiro.');
        }

        return self::$pdo;
    }
}