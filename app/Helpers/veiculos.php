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

if (!function_exists('regras_hibrido')) {
    /**
     * Retorna as regras condicionais para veículos híbridos (HEV, MHEV, PHEV).
     *
     * As regras definem a visibilidade e valores forçados para campos específicos
     * de acordo com o tipo de híbrido selecionado no formulário.
     *
     * A estrutura do array retornado segue o formato definido em config/veiculos.php
     * sob a chave 'regras_hibrido'. Caso a chave não exista, retorna um array vazio.
     *
     * @return array Regras para cada tipo de híbrido (hev, mhev, phev)
     */
    function regras_hibrido(): array
    {
        static $rules = null;

        if ($rules === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $rules = $config['regras_hibrido'] ?? [];
        }

        return $rules;
    }
}