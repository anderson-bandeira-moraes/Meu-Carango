<?php

/**
 * Helper functions for vehicle module.
 * Contains utilities for accessing vehicle configuration data.
 */

if (!function_exists('motorizacoes_list')) {
    /**
     * Retorna a lista de motorizações (cilindradas) para veículos a combustão.
     *
     * O array é carregado a partir do arquivo de configuração config/veiculos.php
     * e mantido em cache estático para evitar múltiplas leituras do arquivo.
     *
     * @return array Lista de strings com as cilindradas (ex: '1.0', '1.6', '2.0')
     */
    function motorizacoes_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['motorizacoes'] ?? [];
        }

        return $list;
    }
}