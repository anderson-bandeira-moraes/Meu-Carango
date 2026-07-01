<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a tabela opcionais.
 * Gerencia operações de leitura da lista de opcionais.
 */
class OpcionalRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Retorna todos os opcionais ordenados por categoria e ordem.
     *
     * @return array
     */
    public function findAll(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT * FROM opcionais ORDER BY categoria, ordem');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar todos os opcionais', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Retorna os opcionais de uma categoria específica, ordenados por ordem.
     *
     * @param string $categoria
     * @return array
     */
    public function findByCategoria(string $categoria): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM opcionais WHERE categoria = ? ORDER BY ordem');
            $stmt->execute([$categoria]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar opcionais por categoria', [
                'categoria' => $categoria,
                'error'     => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Retorna todos os opcionais agrupados por categoria.
     * Útil para renderizar o formulário com seções separadas.
     *
     * @return array Ex: ['Conforto' => [...], 'Segurança' => [...], ...]
     */
    public function findAllGrouped(): array
    {
        try {
            $opcionais = $this->findAll();
            if (empty($opcionais)) {
                return [];
            }

            $agrupados = [];
            foreach ($opcionais as $opcional) {
                $categoria = $opcional['categoria'];
                if (!isset($agrupados[$categoria])) {
                    $agrupados[$categoria] = [];
                }
                $agrupados[$categoria][] = $opcional;
            }

            return $agrupados;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao agrupar opcionais por categoria', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}