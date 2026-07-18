<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação dos campos específicos de veículos híbridos (HEV, MHEV, PHEV).
 *
 * Valida os campos da tabela veiculo_hibrido.
 * Possui validação condicional para campos PHEV (obrigatórios apenas quando tipo = phev).
 */
class VeiculoHibridoRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            // Tipo do híbrido
            'tipo' => 'required|in:hev,mhev,phev',

            // Combustível (ENUM)
            'combustivel' => 'required|in:alcool,diesel,flex,gasolina',

            // Motor a combustão
            'motor_combustao_tipo'          => 'required|max:40',
            'motor_combustao_potencia_cv'   => 'required|numeric|min_num:0',
            'motor_combustao_torque_kgfm'   => 'nullable|numeric|min_num:0',

            // Motor elétrico
            'motor_eletrico_potencia_cv'    => 'required|numeric|min_num:0',
            'motor_eletrico_torque_kgfm'    => 'nullable|numeric|min_num:0',

            // Potência e torque combinados
            'potencia_combinada_cv' => 'required|numeric|min_num:0',
            'torque_combinado_kgfm' => 'nullable|numeric|min_num:0',

            // Tração e transmissão
            'tracao_tipo'        => 'required|max:10',
            'transmissao_tipo'   => 'required|max:30',
            'numero_marchas'     => 'nullable|integer|min_num:0',

            // Bateria
            'bateria_capacidade_kwh' => 'required|numeric|min_num:0',
            'bateria_tipo'           => 'nullable|max:30',

            // Modo elétrico puro
            'modo_eletrico_puro' => 'required|boolean',

            // Autonomias
            'autonomia_eletrica_pbev_km' => 'nullable|integer|min_num:0',
            'autonomia_combinada_km'     => 'nullable|integer|min_num:0',

            // Garantia da bateria
            'bateria_garantia' => 'nullable|max:40',

            // Carregamento (PHEV)
            'carregamento_potencia_ac_kw'   => 'nullable|numeric|min_num:0',
            'carregamento_tempo_ac_horas'   => 'nullable|numeric|min_num:0',
            'carregamento_potencia_dc_kw'   => 'nullable|numeric|min_num:0',
            'carregamento_tipo_conector_ac' => 'nullable|max:20',

            // Consumo
            'consumo_cidade_kml'          => 'required|numeric|min_num:0',
            'consumo_estrada_kml'         => 'required|numeric|min_num:0',
            'consumo_medio_kml'           => 'nullable|numeric|min_num:0',
            'consumo_cidade_etanol_kml'   => 'nullable|numeric|min_num:0',
            'consumo_estrada_etanol_kml'  => 'nullable|numeric|min_num:0',
            'consumo_medio_etanol_kml'    => 'nullable|numeric|min_num:0',

            // Tanque
            'capacidade_tanque_l' => 'required|integer|min_num:0',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            // Tipo
            'tipo.required' => 'O tipo de veículo híbrido é obrigatório.',
            'tipo.in'       => 'O tipo deve ser HEV, MHEV ou PHEV.',

            // Combustível
            'combustivel.required' => 'O tipo de combustível é obrigatório.',
            'combustivel.in'       => 'O tipo de combustível deve ser: álcool, diesel, flex ou gasolina.',

            // Motor a combustão
            'motor_combustao_tipo.required' => 'O tipo do motor a combustão é obrigatório.',
            'motor_combustao_tipo.max'      => 'O tipo do motor a combustão deve ter no máximo :max caracteres.',
            'motor_combustao_potencia_cv.required' => 'A potência do motor a combustão é obrigatória.',
            'motor_combustao_potencia_cv.numeric'  => 'A potência do motor a combustão deve ser um número válido.',
            'motor_combustao_potencia_cv.min_num'  => 'A potência do motor a combustão não pode ser negativa.',
            'motor_combustao_torque_kgfm.numeric'  => 'O torque do motor a combustão deve ser um número válido.',
            'motor_combustao_torque_kgfm.min_num'  => 'O torque do motor a combustão não pode ser negativo.',

            // Motor elétrico
            'motor_eletrico_potencia_cv.required' => 'A potência do motor elétrico é obrigatória.',
            'motor_eletrico_potencia_cv.numeric'   => 'A potência do motor elétrico deve ser um número válido.',
            'motor_eletrico_potencia_cv.min_num'  => 'A potência do motor elétrico não pode ser negativa.',
            'motor_eletrico_torque_kgfm.numeric'  => 'O torque do motor elétrico deve ser um número válido.',
            'motor_eletrico_torque_kgfm.min_num'  => 'O torque do motor elétrico não pode ser negativo.',

            // Potência e torque combinados
            'potencia_combinada_cv.required' => 'A potência combinada é obrigatória.',
            'potencia_combinada_cv.numeric'        => 'A potência combinada deve ser um número válido.',
            'potencia_combinada_cv.min_num'  => 'A potência combinada não pode ser negativa.',
            'torque_combinado_kgfm.required' => 'O torque combinado é obrigatório.',
            'torque_combinado_kgfm.numeric'  => 'O torque combinado deve ser um número válido.',
            'torque_combinado_kgfm.min_num'  => 'O torque combinado não pode ser negativo.',

            // Tração e transmissão
            'tracao_tipo.required' => 'O tipo de tração é obrigatório.',
            'tracao_tipo.max'      => 'O tipo de tração deve ter no máximo :max caracteres.',
            'transmissao_tipo.required' => 'O tipo de transmissão é obrigatório.',
            'transmissao_tipo.max'      => 'O tipo de transmissão deve ter no máximo :max caracteres.',
            'numero_marchas.integer'    => 'O número de marchas deve ser um número inteiro.',
            'numero_marchas.min_num'    => 'O número de marchas não pode ser negativo.',

            // Bateria
            'bateria_capacidade_kwh.required' => 'A capacidade da bateria é obrigatória.',
            'bateria_capacidade_kwh.numeric'  => 'A capacidade da bateria deve ser um número válido.',
            'bateria_capacidade_kwh.min_num'  => 'A capacidade da bateria não pode ser negativa.',
            'bateria_tipo.max'                => 'O tipo da bateria deve ter no máximo :max caracteres.',

            // Modo elétrico puro
            'modo_eletrico_puro.required' => 'O campo modo elétrico puro é obrigatório.',
            'modo_eletrico_puro.boolean'  => 'O campo modo elétrico puro deve ser verdadeiro ou falso.',

            // Autonomias
            'autonomia_eletrica_pbev_km.integer' => 'A autonomia elétrica (PBEV) deve ser um número inteiro.',
            'autonomia_eletrica_pbev_km.min_num' => 'A autonomia elétrica (PBEV) não pode ser negativa.',
            'autonomia_combinada_km.integer'     => 'A autonomia combinada deve ser um número inteiro.',
            'autonomia_combinada_km.min_num'     => 'A autonomia combinada não pode ser negativa.',

            // Garantia da bateria
            'bateria_garantia.max' => 'A garantia da bateria deve ter no máximo :max caracteres.',

            // Carregamento
            'carregamento_potencia_ac_kw.numeric' => 'A potência de carregamento AC deve ser um número válido.',
            'carregamento_potencia_ac_kw.min_num' => 'A potência de carregamento AC não pode ser negativa.',
            'carregamento_tempo_ac_horas.numeric' => 'O tempo de carregamento AC deve ser um número válido.',
            'carregamento_tempo_ac_horas.min_num' => 'O tempo de carregamento AC não pode ser negativo.',
            'carregamento_potencia_dc_kw.numeric'  => 'A potência de carregamento DC deve ser um número válido.',
            'carregamento_potencia_dc_kw.min_num' => 'A potência de carregamento DC não pode ser negativa.',
            'carregamento_tipo_conector_ac.max'   => 'O tipo de conector AC deve ter no máximo :max caracteres.',

            // Consumo
            'consumo_cidade_kml.required' => 'O consumo na cidade é obrigatório.',
            'consumo_cidade_kml.numeric'  => 'O consumo na cidade deve ser um número válido.',
            'consumo_cidade_kml.min_num'  => 'O consumo na cidade não pode ser negativo.',
            'consumo_estrada_kml.required' => 'O consumo na estrada é obrigatório.',
            'consumo_estrada_kml.numeric'  => 'O consumo na estrada deve ser um número válido.',
            'consumo_estrada_kml.min_num'  => 'O consumo na estrada não pode ser negativo.',
            'consumo_medio_kml.numeric'    => 'O consumo médio deve ser um número válido.',
            'consumo_medio_kml.min_num'    => 'O consumo médio não pode ser negativo.',
            'consumo_cidade_etanol_kml.numeric' => 'O consumo na cidade com etanol deve ser um número válido.',
            'consumo_cidade_etanol_kml.min_num' => 'O consumo na cidade com etanol não pode ser negativo.',
            'consumo_estrada_etanol_kml.numeric' => 'O consumo na estrada com etanol deve ser um número válido.',
            'consumo_estrada_etanol_kml.min_num' => 'O consumo na estrada com etanol não pode ser negativo.',
            'consumo_medio_etanol_kml.numeric'  => 'O consumo médio com etanol deve ser um número válido.',
            'consumo_medio_etanol_kml.min_num'  => 'O consumo médio com etanol não pode ser negativo.',

            // Tanque
            'capacidade_tanque_l.required' => 'A capacidade do tanque é obrigatória.',
            'capacidade_tanque_l.integer'  => 'A capacidade do tanque deve ser um número inteiro.',
            'capacidade_tanque_l.min_num'  => 'A capacidade do tanque não pode ser negativa.',
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

        // Converte vírgula decimal para ponto e depois para float
        $floatFields = [
            'motor_combustao_potencia_cv',    
            'motor_combustao_torque_kgfm',
            'motor_eletrico_potencia_cv',    
            'motor_eletrico_torque_kgfm',
            'potencia_combinada_cv',         
            'torque_combinado_kgfm',
            'bateria_capacidade_kwh',
            'carregamento_potencia_ac_kw',
            'carregamento_potencia_dc_kw',   
            'carregamento_tempo_ac_horas',
            'consumo_cidade_kml',
            'consumo_estrada_kml',
            'consumo_medio_kml',
            'consumo_cidade_etanol_kml',
            'consumo_estrada_etanol_kml',
            'consumo_medio_etanol_kml',
        ];

        foreach ($floatFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                // Remove pontos de milhar (ex: 1.500 -> 1500)
                $value = str_replace('.', '', $data[$field]);
                // Converte vírgula para ponto (ex: 12,5 -> 12.5)
                $value = str_replace(',', '.', $value);
                if (is_numeric($value)) {
                    $data[$field] = (float) $value;
                }
            }
        }

        // Campos que permanecem inteiros
        $intFields = [
            'numero_marchas',
            'autonomia_eletrica_pbev_km',
            'autonomia_combinada_km',
            'capacidade_tanque_l',
        ];

        foreach ($intFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        // Normaliza modo_eletrico_puro para 0 ou 1
        if (isset($data['modo_eletrico_puro'])) {
            $data['modo_eletrico_puro'] = (int) (bool) $data['modo_eletrico_puro'];
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * Adiciona validação condicional:
     * - Campos PHEV são obrigatórios apenas quando o tipo for 'phev'.
     * - Campos de consumo com etanol são obrigatórios apenas quando combustivel = 'flex'.
     */
    public function validate(): bool
    {
        // 1. Executa a validação base
        if (!parent::validate()) {
            return false;
        }

        // 2. Obtém os dados validados
        $data = $this->validated();

        // 3. Validação condicional: se for PHEV, campos de carregamento e autonomia são obrigatórios
        if (($data['tipo'] ?? '') === 'phev') {
            $camposPHEV = [
                'autonomia_eletrica_pbev_km'   => 'Autonomia elétrica (PBEV)',
                'carregamento_potencia_ac_kw'  => 'Potência de carregamento AC',
                'carregamento_tipo_conector_ac' => 'Tipo de conector AC',
            ];

            foreach ($camposPHEV as $campo => $label) {
                if (empty($data[$campo]) && $data[$campo] !== 0) {
                    $this->addError($campo, "O campo '{$label}' é obrigatório para veículos PHEV.");
                }
            }
        }

        // 4. Validação condicional: se for Flex, campos de consumo com etanol são obrigatórios
        if (($data['combustivel'] ?? '') === 'flex') {
            $camposFlex = [
                'consumo_cidade_etanol_kml'   => 'Consumo na cidade com etanol',
                'consumo_estrada_etanol_kml'  => 'Consumo na estrada com etanol',
            ];

            foreach ($camposFlex as $campo => $label) {
                if (!isset($data[$campo]) || $data[$campo] === '' || $data[$campo] === null) {
                    $this->addError($campo, "O campo '{$label}' é obrigatório para veículos flex.");
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Retorna os dados da tabela veiculo_hibrido já validados e sanitizados.
     *
     * @return array
     */
    public function getDadosHibridos(): array
    {
        return $this->validated();
    }

    /**
     * Adiciona um erro ao campo (usado internamente).
     *
     * @param string $field
     * @param string $message
     * @return void
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
}