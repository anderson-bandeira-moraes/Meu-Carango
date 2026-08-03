<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação dos campos do kit GNV (tabela veiculo_gnv).
 *
 * Este Request só é chamado quando gnv_instalado = 1.
 * Todos os campos obrigatórios são exigidos sem validação condicional.
 */
class VeiculoGNVRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            // Sistema e geração
            'tipo_sistema'     => 'required|in:GNC,GLP',
            'geracao_kit'      => 'required|in:3ª,4ª,5ª,6ª',
            'marca_kit'        => 'nullable|max:40',

            // Datas
            'data_instalacao'          => 'nullable|date',
            'data_inspecao'            => 'nullable|date',
            'data_validade_cilindro'   => 'nullable|date',

            // Documentação (CSV e Selo GNV)
            'possui_csv'               => 'nullable|boolean',
            'possui_selo_gnv'          => 'nullable|boolean',

            // Cilindro
            'capacidade_cilindro_m3' => 'required|numeric|min_num:0',
            'quantidade_cilindros'   => 'required|integer|min_num:0',
            'material_cilindro'      => 'nullable|max:40',
            'localizacao_cilindro'   => 'required|max:40',

            // Consumo (opcionais)
            'consumo_cidade_m3km'   => 'nullable|numeric|min_num:0',
            'consumo_estrada_m3km'  => 'nullable|numeric|min_num:0',

            // Autonomia (opcionais)
            'autonomia_media_km'    => 'nullable|integer|min_num:0',
            'autonomia_cidade_km'   => 'nullable|integer|min_num:0',
            'autonomia_estrada_km'  => 'nullable|integer|min_num:0',

            // Instaladora e observações
            'instaladora_certificada' => 'nullable|max:50',
            'observacoes'             => 'nullable|string',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            // Sistema
            'tipo_sistema.required' => 'O tipo de sistema GNV é obrigatório.',
            'tipo_sistema.in'       => 'O tipo de sistema deve ser GNC ou GLP.',

            // Geração
            'geracao_kit.required' => 'A geração do kit GNV é obrigatória.',
            'geracao_kit.in'       => 'A geração do kit deve ser 3ª, 4ª, 5ª ou 6ª geração.',

            // Marca
            'marca_kit.max' => 'A marca do kit deve ter no máximo :max caracteres.',

            // Datas
            'data_instalacao.date'            => 'A data de instalação deve ser uma data válida.',
            'data_inspecao.date'              => 'A data da inspeção deve ser uma data válida.',
            'data_validade_cilindro.date'     => 'A data de validade do cilindro deve ser uma data válida.',

            // Documentação
            'possui_csv.boolean'      => 'O campo CSV deve ser verdadeiro ou falso.',
            'possui_selo_gnv.boolean' => 'O campo selo GNV deve ser verdadeiro ou falso.',

            // Cilindro
            'capacidade_cilindro_m3.required' => 'A capacidade do cilindro em m³ é obrigatória.',
            'capacidade_cilindro_m3.numeric'  => 'A capacidade do cilindro deve ser um número válido.',
            'capacidade_cilindro_m3.min_num'  => 'A capacidade do cilindro não pode ser negativa.',
            'quantidade_cilindros.required'   => 'A quantidade de cilindros é obrigatória.',
            'quantidade_cilindros.integer'    => 'A quantidade de cilindros deve ser um número inteiro.',
            'quantidade_cilindros.min_num'    => 'A quantidade de cilindros não pode ser negativa.',
            'material_cilindro.max'           => 'O material do cilindro deve ter no máximo :max caracteres.',
            'localizacao_cilindro.max'        => 'A localização do cilindro deve ter no máximo :max caracteres.',

            // Consumo
            'consumo_cidade_m3km.numeric'  => 'O consumo na cidade em m³/km deve ser um número válido.',
            'consumo_cidade_m3km.min_num'  => 'O consumo na cidade em m³/km não pode ser negativo.',
            'consumo_estrada_m3km.numeric' => 'O consumo na estrada em m³/km deve ser um número válido.',
            'consumo_estrada_m3km.min_num' => 'O consumo na estrada em m³/km não pode ser negativo.',

            // Autonomia
            'autonomia_media_km.integer'   => 'A autonomia média deve ser um número válido.',
            'autonomia_media_km.min_num'   => 'A autonomia média não pode ser negativa.',
            'autonomia_cidade_km.integer'  => 'A autonomia na cidade deve ser um número válido.',
            'autonomia_cidade_km.min_num'  => 'A autonomia na cidade não pode ser negativa.',
            'autonomia_estrada_km.integer' => 'A autonomia na estrada deve ser um número válido.',
            'autonomia_estrada_km.min_num' => 'A autonomia na estrada não pode ser negativa.',

            // Instaladora
            'instaladora_certificada.max' => 'A instaladora certificada deve ter no máximo :max caracteres.',
            'observacoes.string'          => 'As observações devem ser um texto válido.',
        ];
    }

    /**
     * Sobrescreve a sanitização para aplicar regras específicas.
     *
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data): array
    {
        $data = parent::sanitize($data);

        // 1. Converter strings vazias para NULL em TODOS os campos (independente de obrigatoriedade)
        foreach ($data as $key => $value) {
            if (is_string($value) && $value === '') {
                $data[$key] = null;
            }
        }

        // 2. Listas de campos por tipo
        $intFields = [
            'quantidade_cilindros',
            'autonomia_media_km',
            'autonomia_cidade_km',
            'autonomia_estrada_km'
        ];

        $decimalFields = [
            'capacidade_cilindro_m3',
            'capacidade_cilindro_m3_outro',
            'consumo_cidade_m3km',
            'consumo_estrada_m3km'
        ];

        $stringFields = [
            'marca_kit',
            'material_cilindro_outro',
            'localizacao_cilindro_outro',
            'instaladora_certificada',
            'observacoes'
        ];

        $booleanFields = [
            'possui_csv',
            'possui_selo_gnv'
        ];

        $dateFields = [
            'data_instalacao',
            'data_inspecao',
            'data_validade_cilindro'
        ];

        // 3. Converter inteiros
        foreach ($intFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        // 4. Converter decimais (com tratamento de vírgula/ponto)
        foreach ($decimalFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $value = trim($data[$field]);
                // Converte vírgula para ponto (caso venha do front)
                $value = str_replace(',', '.', $value);
                // Remove caracteres não numéricos (exceto ponto decimal)
                $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                if (is_numeric($value)) {
                    $data[$field] = (float) $value;
                }
            } elseif (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }

        // 5. Sanitizar strings (trim e null se vazio)
        foreach ($stringFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
                // Reforça a conversão para null (já feita no passo 1, mas mantido por segurança)
                if ($data[$field] === '') {
                    $data[$field] = null;
                }
            }
        }

        // 6. Converter booleanos
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) (bool) $data[$field];
            }
        }

        // 7. Converter datas do formato brasileiro (dd/mm/aaaa) para Y-m-d
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && $data[$field] !== '') {
                // Tenta converter se estiver no formato dd/mm/aaaa
                $parts = explode('/', $data[$field]);
                if (count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2])) {
                    $data[$field] = sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
                }
            }
        }

        return $data;
    }

    /**
     * Retorna os dados da tabela veiculo_gnv já validados e sanitizados.
     *
     * @return array
     */
    public function getDadosGNV(): array
    {
        return $this->validated();
    }
}

/**
 * ========================================================================
 * FORM REQUEST PARA DADOS DO KIT GNV
 * ========================================================================
 * 
 * Este FormRequest gerencia a validação e sanitização dos campos
 * específicos do kit GNV (tabela `veiculo_gnv`).
 * 
 * CARACTERÍSTICAS PRINCIPAIS:
 * 
 * 1. VALIDAÇÃO DECLARATIVA (rules())
 *    - Define regras por campo (obrigatoriedade, tipo, valores permitidos)
 *    - Campos obrigatórios: tipo_sistema, geracao_kit, capacidade_cilindro_m3,
 *      quantidade_cilindros, localizacao_cilindro
 *    - Campos opcionais (nullable): marca_kit, datas, consumos, autonomias,
 *      documentação, instaladora, observações
 *    - Usa `in` para campos com valores fixos (ex: GNC, GLP)
 *    - Usa `date` para campos de data
 *    - Autonomias são `integer` (km), capacidade e consumo são `numeric`
 * 
 * 2. MENSAGENS PERSONALIZADAS (messages())
 *    - Mensagens para cada regra de cada campo
 *    - Inclui mensagens para `required`, `in`, `max`, `numeric`, `integer`, `min_num`, `date`
 * 
 * 3. SANITIZAÇÃO GENÉRICA (sanitize())
 *    - **Passo 1:** Converter TODAS as strings vazias para `NULL`.
 *      Isso unifica valores vazios para todos os campos.
 *    - **Passo 2:** Aplicar conversão de tipo conforme listas:
 *        - `$intFields`    → (int) para quantidade_cilindros e autonomias (km)
 *        - `$decimalFields` → (float) para capacidade e consumo (m³, km/m³)
 *        - `$stringFields`  → trim() e null se vazio (marca, materiais, localização, instaladora, observações)
 *        - `$booleanFields` → (int) (bool) para possui_csv e possui_selo_gnv (0/1)
 *        - `$dateFields`    → converte datas do formato BR (dd/mm/aaaa) para ISO (Y-m-d)
 *    - **Importante:** A sanitização NÃO distingue obrigatórios de opcionais.
 *      A validação `required` é feita posteriormente no `validate()`.
 * 
 * 4. MÉTODOS AUXILIARES
 *    - `getDadosGNV()` → retorna os dados validados para uso no Service
 * 
 * ========================================================================
 * FLUXO DE EXECUÇÃO:
 * 
 * 1. Dados chegam → `sanitize()` limpa e converte tipos
 * 2. `validate()` executa regras base (parent::validate())
 * 3. Dados validados ficam disponíveis em `validated()`
 * 4. Controller chama `getDadosGNV()` para obter dados limpos
 * 
 * ========================================================================
 */