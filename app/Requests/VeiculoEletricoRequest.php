<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação dos campos específicos de veículos 100% elétricos (BEV).
 *
 * Valida os campos da tabela veiculo_eletrico.
 * Não há validação condicional, pois os campos são independentes.
 */
class VeiculoEletricoRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            // Tração e transmissão
            'tracao_tipo'       => 'required|max:10',
            'transmissao_tipo'  => 'required|max:30',

            // Motorização
            'potencia_max_cv'   => 'required|numeric|min_num:0',

            // Torque (opcionais)
            'torque_max_nm'     => 'nullable|numeric|min_num:0',
            'torque_max_kgfm'   => 'nullable|numeric|min_num:0',

            // Desempenho (opcionais)
            'aceleracao_0_100_seg' => 'nullable|numeric|min_num:0',
            'velocidade_max_kmh'   => 'nullable|integer|min_num:0',

            // Bateria
            'bateria_tipo'            => 'required|max:30',
            'capacidade_liquida_kwh'  => 'required|numeric|min_num:0',
            'saude_bateria_soh'       => 'nullable|numeric|between:0,100',
            'sistema_eletrico_tensao' => 'nullable|max:10',

            // Autonomia
            'autonomia_wltp_km'    => 'nullable|integer|min_num:0',
            'autonomia_inmetro_km' => 'required|integer|min_num:0',

            // Garantia (opcional)
            'garantia_bateria' => 'nullable|max:40',

            // Carregamento DC
            'potencia_max_dc_kw' => 'required|numeric|min_num:0',
            'tipo_conector_dc'   => 'required|max:20',

            // Carregamento AC (opcional)
            'tipo_conector_ac' => 'nullable|max:20',

            // Tempo de carga (opcional)
            'tempo_carga_dc_min' => 'nullable|numeric|min_num:0',

            // Consumo energético (opcional)
            'consumo_energetico_kwh_100km' => 'nullable|numeric|min_num:0',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            // Tração
            'tracao_tipo.required' => 'O tipo de tração é obrigatório.',
            'tracao_tipo.max'      => 'O tipo de tração deve ter no máximo :max caracteres.',

            // Transmissão
            'transmissao_tipo.required' => 'O tipo de transmissão é obrigatório.',
            'transmissao_tipo.max'      => 'O tipo de transmissão deve ter no máximo :max caracteres.',

            // Potência máxima
            'potencia_max_cv.required'    => 'A potência máxima é obrigatória.',
            'potencia_max_cv.numeric'     => 'A potência máxima deve ser um número válido.',
            'potencia_max_cv.min_num'     => 'A potência máxima não pode ser negativa.',

            // Torque (opcionais)
            'torque_max_nm.numeric'       => 'O torque (Nm) deve ser um número válido.',
            'torque_max_nm.min_num'       => 'O torque (Nm) não pode ser negativo.',
            'torque_max_kgfm.numeric'     => 'O torque (kgfm) deve ser um número válido.',
            'torque_max_kgfm.min_num'     => 'O torque (kgfm) não pode ser negativo.',

            // Desempenho
            'aceleracao_0_100_seg.numeric' => 'A aceleração 0-100 deve ser um número válido.',
            'aceleracao_0_100_seg.min_num' => 'A aceleração 0-100 não pode ser negativa.',
            'velocidade_max_kmh.integer'   => 'A velocidade máxima deve ser um número inteiro.',
            'velocidade_max_kmh.min_num'   => 'A velocidade máxima não pode ser negativa.',

            // Bateria
            'capacidade_liquida_kwh.required' => 'A capacidade da bateria é obrigatória.',
            'capacidade_liquida_kwh.numeric'  => 'A capacidade da bateria deve ser um número válido.',
            'capacidade_liquida_kwh.min_num'  => 'A capacidade da bateria não pode ser negativa.',
            'bateria_tipo.max'                => 'O tipo de bateria deve ter no máximo :max caracteres.',
            'sistema_eletrico_tensao.max'     => 'A tensão da bateria deve ter no máximo :max caracteres.',

            'saude_bateria_soh.numeric'  => 'A saúde da bateria deve ser um número válido.',
            'saude_bateria_soh.between'  => 'A saúde da bateria deve estar entre 0 e 100%.',

            // Autonomia
            'autonomia_wltp_km.integer' => 'A autonomia WLTP deve ser um número inteiro.',
            'autonomia_wltp_km.min_num' => 'A autonomia WLTP não pode ser negativa.',
            'autonomia_inmetro_km.required' => 'A autonomia Inmetro é obrigatória.',
            'autonomia_inmetro_km.integer'  => 'A autonomia Inmetro deve ser um número inteiro.',
            'autonomia_inmetro_km.min_num'  => 'A autonomia Inmetro não pode ser negativa.',

            // Garantia
            'garantia_bateria.max' => 'A garantia da bateria deve ter no máximo :max caracteres.',

            // Carregamento DC
            'potencia_max_dc_kw.required' => 'A potência máxima de carregamento DC é obrigatória.',
            'potencia_max_dc_kw.numeric'  => 'A potência máxima de carregamento DC deve ser um número válido.',
            'potencia_max_dc_kw.min_num'  => 'A potência máxima de carregamento DC não pode ser negativa.',

            'tipo_conector_dc.required' => 'O tipo de conector DC é obrigatório.',
            'tipo_conector_dc.max'      => 'O tipo de conector DC deve ter no máximo :max caracteres.',

            // Carregamento AC (opcional)
            'tipo_conector_ac.max' => 'O tipo de conector AC deve ter no máximo :max caracteres.',

            // Tempo de carga (opcional)
            'tempo_carga_dc_min.numeric'  => 'O tempo de carga DC deve ser um número válido.',
            'tempo_carga_dc_min.min_num' => 'O tempo de carga DC não pode ser negativo.',

            // Consumo energético (opcional)
            'consumo_energetico_kwh_100km.numeric' => 'O consumo energético deve ser um número válido.',
            'consumo_energetico_kwh_100km.min_num' => 'O consumo energético não pode ser negativo.',
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
            'potencia_max_cv',          // <-- ADICIONADO
            'torque_max_nm',            // <-- ADICIONADO
            'torque_max_kgfm',
            'aceleracao_0_100_seg',
            'capacidade_liquida_kwh',
            'saude_bateria_soh',
            'consumo_energetico_kwh_100km',
            'potencia_max_dc_kw',       // <-- ADICIONADO
            'tempo_carga_dc_min',       // <-- ADICIONADO
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

        // Os campos que permanecem inteiros
        $intFields = [
            'velocidade_max_kmh',
            'autonomia_wltp_km',
            'autonomia_inmetro_km',
            // 'potencia_max_cv' removido
            // 'torque_max_nm' removido
            // 'potencia_max_dc_kw' removido
            // 'tempo_carga_dc_min' removido
        ];

        foreach ($intFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        // Sanitização de textos (existente)
        $textFields = ['bateria_tipo'];
        foreach ($textFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        // Normaliza sistema_eletrico_tensao
        if (isset($data['sistema_eletrico_tensao']) && is_string($data['sistema_eletrico_tensao'])) {
            $data['sistema_eletrico_tensao'] = trim($data['sistema_eletrico_tensao']);
        }

        return $data;
    }

    /**
     * Retorna os dados da tabela veiculo_eletrico já validados e sanitizados.
     *
     * @return array
     */
    public function getDadosEletricos(): array
    {
        return $this->validated();
    }
}