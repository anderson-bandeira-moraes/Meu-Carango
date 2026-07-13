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
            'potencia_max_cv'   => 'required|integer|min_num:0',

            // Torque (opcionais)
            'torque_max_nm'     => 'nullable|integer|min_num:0',
            'torque_max_kgfm'   => 'nullable|numeric|min_num:0',

            // Desempenho (opcionais)
            'aceleracao_0_100_seg' => 'nullable|numeric|min_num:0',
            'velocidade_max_kmh'   => 'nullable|integer|min_num:0',

            // Bateria
            'capacidade_liquida_kwh' => 'required|numeric|min_num:0',
            'saude_bateria_soh'      => 'required|numeric|between:0,100',

            // Autonomia
            'autonomia_wltp_km'    => 'nullable|integer|min_num:0',
            'autonomia_inmetro_km' => 'required|integer|min_num:0',

            // Garantia (opcional)
            'garantia_bateria' => 'nullable|max:40',

            // Carregamento DC
            'potencia_max_dc_kw' => 'required|integer|min_num:0',
            'tipo_conector_dc'   => 'required|max:20',

            // Carregamento AC (opcional)
            'tipo_conector_ac' => 'nullable|max:20',

            // Tempo de carga (opcional)
            'tempo_carga_dc_min' => 'nullable|integer|min_num:0',

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
            'potencia_max_cv.integer'     => 'A potência máxima deve ser um número inteiro.',
            'potencia_max_cv.min_num'     => 'A potência máxima não pode ser negativa.',

            // Torque (opcionais)
            'torque_max_nm.integer'       => 'O torque (Nm) deve ser um número inteiro.',
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

            'saude_bateria_soh.required' => 'A saúde da bateria (SoH) é obrigatória.',
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
            'potencia_max_dc_kw.integer'  => 'A potência máxima de carregamento DC deve ser um número inteiro.',
            'potencia_max_dc_kw.min_num'  => 'A potência máxima de carregamento DC não pode ser negativa.',

            'tipo_conector_dc.required' => 'O tipo de conector DC é obrigatório.',
            'tipo_conector_dc.max'      => 'O tipo de conector DC deve ter no máximo :max caracteres.',

            // Carregamento AC (opcional)
            'tipo_conector_ac.max' => 'O tipo de conector AC deve ter no máximo :max caracteres.',

            // Tempo de carga (opcional)
            'tempo_carga_dc_min.integer' => 'O tempo de carga DC deve ser um número inteiro.',
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

        // Converte campos numéricos para float/int onde apropriado
        $floatFields = [
            'torque_max_kgfm',
            'aceleracao_0_100_seg',
            'capacidade_liquida_kwh',
            'saude_bateria_soh',
            'consumo_energetico_kwh_100km',
        ];
        foreach ($floatFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }

        $intFields = [
            'potencia_max_cv',
            'torque_max_nm',
            'velocidade_max_kmh',
            'autonomia_wltp_km',
            'autonomia_inmetro_km',
            'potencia_max_dc_kw',
            'tempo_carga_dc_min',
        ];
        foreach ($intFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
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
        $validated = $this->validated();

        return [
            'tracao_tipo'                   => $validated['tracao_tipo'] ?? null,
            'transmissao_tipo'              => $validated['transmissao_tipo'] ?? null,
            'potencia_max_cv'               => $validated['potencia_max_cv'] ?? null,
            'torque_max_nm'                 => $validated['torque_max_nm'] ?? null,
            'torque_max_kgfm'               => $validated['torque_max_kgfm'] ?? null,
            'aceleracao_0_100_seg'          => $validated['aceleracao_0_100_seg'] ?? null,
            'velocidade_max_kmh'            => $validated['velocidade_max_kmh'] ?? null,
            'capacidade_liquida_kwh'        => $validated['capacidade_liquida_kwh'] ?? null,
            'saude_bateria_soh'             => $validated['saude_bateria_soh'] ?? null,
            'autonomia_wltp_km'             => $validated['autonomia_wltp_km'] ?? null,
            'autonomia_inmetro_km'          => $validated['autonomia_inmetro_km'] ?? null,
            'garantia_bateria'              => $validated['garantia_bateria'] ?? null,
            'potencia_max_dc_kw'            => $validated['potencia_max_dc_kw'] ?? null,
            'tipo_conector_dc'              => $validated['tipo_conector_dc'] ?? null,
            'tipo_conector_ac'              => $validated['tipo_conector_ac'] ?? null,
            'tempo_carga_dc_min'            => $validated['tempo_carga_dc_min'] ?? null,
            'consumo_energetico_kwh_100km'  => $validated['consumo_energetico_kwh_100km'] ?? null,
        ];
    }
}