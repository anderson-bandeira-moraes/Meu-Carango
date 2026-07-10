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

if (!function_exists('logo_url')) {
    /**
     * Retorna a URL pública para a logo de uma marca.
     *
     * @param string|null $caminho Caminho relativo da imagem (ex: 'marcas/logo_fiat_12345.webp')
     * @return string URL pública da imagem ou URL da imagem padrão se não houver logo.
     */
    function logo_url(?string $caminho): string
    {
        if (empty($caminho)) {
            return '/assets/images/default-brand.png';
        }
        // Agora a URL pública é /uploads/ + caminho (ex: 'marcas/arquivo.webp')
        return '/uploads/' . ltrim($caminho, '/');
    }
}

if (!function_exists('cores_list')) {
    /**
     * Retorna a lista de cores disponíveis (nome => código hexadecimal).
     *
     * @return array Array associativo com nome da cor => código hexadecimal
     */
    function cores_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['cores'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('portas_list')) {
    /**
     * Retorna a lista de opções de número de portas (value => label).
     *
     * @return array Array associativo com valor => label
     */
    function portas_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['portas'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('assentos_list')) {
    /**
     * Retorna a lista de opções de número de assentos (array de valores numéricos).
     *
     * @return array Array de valores inteiros
     */
    function assentos_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['assentos'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('combustiveis_list')) {
    /**
     * Retorna a lista de tipos de combustível (value => label).
     *
     * @return array Array associativo com value => label
     */
    function combustiveis_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['combustiveis'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('tracao_list')) {
    /**
     * Retorna a lista de tipos de tração (value => label).
     *
     * @return array Array associativo com value => label
     */
    function tracao_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['tracao'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('transmissoes_list')) {
    /**
     * Retorna a lista de tipos de transmissão por contexto (value => label).
     *
     * Estrutura retornada:
     * [
     *     'combustao' => [ 'Manual' => 'Manual (MT)', ... ],
     *     'eletrico'  => [ 'Relação Única' => 'Relação Única (Fixed-Ratio)', ... ],
     *     'hibrido'   => [ 'e-CVT' => 'e-CVT', ... ],
     * ]
     *
     * @return array Array com subarrays por contexto
     */
    function transmissoes_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['transmissoes'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('marchas_list')) {
    /**
     * Retorna a lista de opções de número de marchas (valores numéricos).
     *
     * @return array Array de valores inteiros (4 a 10)
     */
    function marchas_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['marchas'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('gnv_sistemas_list')) {
    /**
     * Retorna a lista de tipos de sistema GNV (value => label).
     *
     * @return array Array associativo com value => label
     */
    function gnv_sistemas_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['gnv_sistemas'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('gnv_geracoes_list')) {
    /**
     * Retorna a lista de gerações do kit GNV (value => label).
     *
     * @return array Array associativo com value => label
     */
    function gnv_geracoes_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['gnv_geracoes'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('gnv_materiais_list')) {
    /**
     * Retorna a lista de materiais do cilindro GNV (value => label).
     *
     * @return array Array associativo com value => label
     */
    function gnv_materiais_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['gnv_materiais'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('gnv_localizacoes_list')) {
    /**
     * Retorna a lista de localizações do cilindro GNV (value => label).
     *
     * @return array Array associativo com value => label
     */
    function gnv_localizacoes_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['gnv_localizacoes'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('gnv_capacidades_list')) {
    /**
     * Retorna a lista de capacidades do cilindro GNV em m³ (valores numéricos).
     *
     * @return array Array de valores float
     */
    function gnv_capacidades_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['gnv_capacidades'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('conectores_dc_list')) {
    /**
     * Retorna a lista de conectores DC para veículos elétricos (value => label).
     *
     * @return array Array associativo com value => label
     */
    function conectores_dc_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['conectores_dc'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('conectores_ac_list')) {
    /**
     * Retorna a lista de conectores AC para veículos elétricos e PHEV (value => label).
     *
     * @return array Array associativo com value => label
     */
    function conectores_ac_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['conectores_ac'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('tipos_hibrido_list')) {
    /**
     * Retorna a lista de tipos de veículos híbridos (value => label).
     *
     * @return array Array associativo com value => label
     */
    function tipos_hibrido_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['tipos_hibrido'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('baterias_tipos_list')) {
    /**
     * Retorna a lista de tipos de bateria para veículos híbridos e elétricos (value => label).
     *
     * @return array Array associativo com value => label
     */
    function baterias_tipos_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['baterias_tipos'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('status_estoque_list')) {
    /**
     * Retorna a lista de status de estoque (value => label).
     *
     * @return array Array associativo com value => label
     */
    function status_estoque_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['status_estoque'] ?? [];
        }

        return $list;
    }
}

if (!function_exists('status_vitrine_list')) {
    /**
     * Retorna a lista de status de vitrine (value => label).
     *
     * @return array Array associativo com value => label
     */
    function status_vitrine_list(): array
    {
        static $list = null;

        if ($list === null) {
            $config = require CONFIG_DIR . '/veiculos.php';
            $list = $config['status_vitrine'] ?? [];
        }

        return $list;
    }
}