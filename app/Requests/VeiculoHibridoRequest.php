<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação dos campos específicos de veículos híbridos (HEV, MHEV, PHEV).
 *
 * Valida os campos da tabela veiculo_hibrido.
 * Não há validação condicional, conforme definido.
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
            'motor_combustao_potencia_cv'   => 'required|integer|min:0',
            'motor_combustao_torque_kgfm'   => 'nullable|numeric|min:0',

            // Motor elétrico
            'motor_eletrico_potencia_cv'    => 'required|integer|min:0',
            'motor_eletrico_torque_kgfm'    => 'nullable|numeric|min:0',

            // Potência e torque combinados
            'potencia_combinada_cv' => 'required|integer|min:0',
            'torque_combinado_kgfm' => 'required|numeric|min:0',

            // Tração e transmissão
            'tracao_tipo'        => 'required|max:10',
            'transmissao_tipo'   => 'required|max:30',
            'numero_marchas'     => 'nullable|integer|min:0',

            // Bateria
            'bateria_capacidade_kwh' => 'required|numeric|min:0',
            'bateria_tipo'           => 'nullable|max:30',

            // Modo elétrico puro
            'modo_eletrico_puro' => 'required|boolean',

            // Autonomias
            'autonomia_eletrica_pbev_km' => 'nullable|integer|min:0',
            'autonomia_combinada_km'     => 'nullable|integer|min:0',

            // Garantia da bateria
            'bateria_garantia' => 'nullable|max:40',

            // Carregamento (PHEV)
            'carregamento_potencia_ac_kw' => 'nullable|numeric|min:0',
            'carregamento_tempo_ac_horas' => 'nullable|numeric|min:0',
            'carregamento_potencia_dc_kw' => 'nullable|integer|min:0',
            'carregamento_tipo_conector_ac' => 'nullable|max:20',

            // Consumo
            'consumo_cidade_kml'   => 'required|numeric|min:0',
            'consumo_estrada_kml'  => 'required|numeric|min:0',
            'consumo_medio_kml'    => 'nullable|numeric|min:0',

            // Tanque
            'capacidade_tanque_l' => 'required|integer|min:0',
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
            'motor_combustao_potencia_cv.integer'  => 'A potência do motor a combustão deve ser um número inteiro.',
            'motor_combustao_potencia_cv.min'      => 'A potência do motor a combustão não pode ser negativa.',
            'motor_combustao_torque_kgfm.numeric'  => 'O torque do motor a combustão deve ser um número válido.',
            'motor_combustao_torque_kgfm.min'      => 'O torque do motor a combustão não pode ser negativo.',

            // Motor elétrico
            'motor_eletrico_potencia_cv.required' => 'A potência do motor elétrico é obrigatória.',
            'motor_eletrico_potencia_cv.integer'  => 'A potência do motor elétrico deve ser um número inteiro.',
            'motor_eletrico_potencia_cv.min'      => 'A potência do motor elétrico não pode ser negativa.',
            'motor_eletrico_torque_kgfm.numeric'  => 'O torque do motor elétrico deve ser um número válido.',
            'motor_eletrico_torque_kgfm.min'      => 'O torque do motor elétrico não pode ser negativo.',

            // Potência e torque combinados
            'potencia_combinada_cv.required' => 'A potência combinada é obrigatória.',
            'potencia_combinada_cv.integer'  => 'A potência combinada deve ser um número inteiro.',
            'potencia_combinada_cv.min'      => 'A potência combinada não pode ser negativa.',
            'torque_combinado_kgfm.required' => 'O torque combinado é obrigatório.',
            'torque_combinado_kgfm.numeric'  => 'O torque combinado deve ser um número válido.',
            'torque_combinado_kgfm.min'      => 'O torque combinado não pode ser negativo.',

            // Tração e transmissão
            'tracao_tipo.required' => 'O tipo de tração é obrigatório.',
            'tracao_tipo.max'      => 'O tipo de tração deve ter no máximo :max caracteres.',
            'transmissao_tipo.required' => 'O tipo de transmissão é obrigatório.',
            'transmissao_tipo.max'      => 'O tipo de transmissão deve ter no máximo :max caracteres.',
            'numero_marchas.integer'    => 'O número de marchas deve ser um número inteiro.',
            'numero_marchas.min'        => 'O número de marchas não pode ser negativo.',

            // Bateria
            'bateria_capacidade_kwh.required' => 'A capacidade da bateria é obrigatória.',
            'bateria_capacidade_kwh.numeric'  => 'A capacidade da bateria deve ser um número válido.',
            'bateria_capacidade_kwh.min'      => 'A capacidade da bateria não pode ser negativa.',
            'bateria_tipo.max'                => 'O tipo da bateria deve ter no máximo :max caracteres.',

            // Modo elétrico puro
            'modo_eletrico_puro.required' => 'O campo modo elétrico puro é obrigatório.',
            'modo_eletrico_puro.boolean'  => 'O campo modo elétrico puro deve ser verdadeiro ou falso.',

            // Autonomias
            'autonomia_eletrica_pbev_km.integer' => 'A autonomia elétrica (PBEV) deve ser um número inteiro.',
            'autonomia_eletrica_pbev_km.min'     => 'A autonomia elétrica (PBEV) não pode ser negativa.',
            'autonomia_combinada_km.integer'     => 'A autonomia combinada deve ser um número inteiro.',
            'autonomia_combinada_km.min'         => 'A autonomia combinada não pode ser negativa.',

            // Garantia da bateria
            'bateria_garantia.max' => 'A garantia da bateria deve ter no máximo :max caracteres.',

            // Carregamento
            'carregamento_potencia_ac_kw.numeric' => 'A potência de carregamento AC deve ser um número válido.',
            'carregamento_potencia_ac_kw.min'     => 'A potência de carregamento AC não pode ser negativa.',
            'carregamento_tempo_ac_horas.numeric' => 'O tempo de carregamento AC deve ser um número válido.',
            'carregamento_tempo_ac_horas.min'     => 'O tempo de carregamento AC não pode ser negativo.',
            'carregamento_potencia_dc_kw.integer' => 'A potência de carregamento DC deve ser um número inteiro.',
            'carregamento_potencia_dc_kw.min'     => 'A potência de carregamento DC não pode ser negativa.',
            'carregamento_tipo_conector_ac.max'   => 'O tipo de conector AC deve ter no máximo :max caracteres.',

            // Consumo
            'consumo_cidade_kml.required' => 'O consumo na cidade é obrigatório.',
            'consumo_cidade_kml.numeric'  => 'O consumo na cidade deve ser um número válido.',
            'consumo_cidade_kml.min'      => 'O consumo na cidade não pode ser negativo.',
            'consumo_estrada_kml.required' => 'O consumo na estrada é obrigatório.',
            'consumo_estrada_kml.numeric'  => 'O consumo na estrada deve ser um número válido.',
            'consumo_estrada_kml.min'      => 'O consumo na estrada não pode ser negativo.',
            'consumo_medio_kml.numeric'    => 'O consumo médio deve ser um número válido.',
            'consumo_medio_kml.min'        => 'O consumo médio não pode ser negativo.',

            // Tanque
            'capacidade_tanque_l.required' => 'A capacidade do tanque é obrigatória.',
            'capacidade_tanque_l.integer'  => 'A capacidade do tanque deve ser um número inteiro.',
            'capacidade_tanque_l.min'      => 'A capacidade do tanque não pode ser negativa.',
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
            'motor_combustao_torque_kgfm',
            'motor_eletrico_torque_kgfm',
            'torque_combinado_kgfm',
            'bateria_capacidade_kwh',
            'carregamento_potencia_ac_kw',
            'carregamento_tempo_ac_horas',
            'consumo_cidade_kml',
            'consumo_estrada_kml',
            'consumo_medio_kml',
        ];
        foreach ($floatFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }

        $intFields = [
            'motor_combustao_potencia_cv',
            'motor_eletrico_potencia_cv',
            'potencia_combinada_cv',
            'numero_marchas',
            'autonomia_eletrica_pbev_km',
            'autonomia_combinada_km',
            'carregamento_potencia_dc_kw',
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
     * Retorna os dados da tabela veiculo_hibrido já validados e sanitizados.
     *
     * @return array
     */
    public function getDadosHibridos(): array
    {
        $validated = $this->validated();

        return [
            'tipo'                              => $validated['tipo'] ?? null,
            'combustivel'                       => $validated['combustivel'] ?? null,
            'motor_combustao_tipo'              => $validated['motor_combustao_tipo'] ?? null,
            'motor_combustao_potencia_cv'       => $validated['motor_combustao_potencia_cv'] ?? null,
            'motor_combustao_torque_kgfm'       => $validated['motor_combustao_torque_kgfm'] ?? null,
            'motor_eletrico_potencia_cv'        => $validated['motor_eletrico_potencia_cv'] ?? null,
            'motor_eletrico_torque_kgfm'        => $validated['motor_eletrico_torque_kgfm'] ?? null,
            'potencia_combinada_cv'             => $validated['potencia_combinada_cv'] ?? null,
            'torque_combinado_kgfm'             => $validated['torque_combinado_kgfm'] ?? null,
            'tracao_tipo'                       => $validated['tracao_tipo'] ?? null,
            'transmissao_tipo'                  => $validated['transmissao_tipo'] ?? null,
            'numero_marchas'                    => $validated['numero_marchas'] ?? null,
            'bateria_capacidade_kwh'            => $validated['bateria_capacidade_kwh'] ?? null,
            'bateria_tipo'                      => $validated['bateria_tipo'] ?? null,
            'modo_eletrico_puro'                => $validated['modo_eletrico_puro'] ?? 0,
            'autonomia_eletrica_pbev_km'        => $validated['autonomia_eletrica_pbev_km'] ?? null,
            'autonomia_combinada_km'            => $validated['autonomia_combinada_km'] ?? null,
            'bateria_garantia'                  => $validated['bateria_garantia'] ?? null,
            'carregamento_potencia_ac_kw'       => $validated['carregamento_potencia_ac_kw'] ?? null,
            'carregamento_tempo_ac_horas'       => $validated['carregamento_tempo_ac_horas'] ?? null,
            'carregamento_potencia_dc_kw'       => $validated['carregamento_potencia_dc_kw'] ?? null,
            'carregamento_tipo_conector_ac'     => $validated['carregamento_tipo_conector_ac'] ?? null,
            'consumo_cidade_kml'                => $validated['consumo_cidade_kml'] ?? null,
            'consumo_estrada_kml'               => $validated['consumo_estrada_kml'] ?? null,
            'consumo_medio_kml'                 => $validated['consumo_medio_kml'] ?? null,
            'capacidade_tanque_l'               => $validated['capacidade_tanque_l'] ?? null,
        ];
    }
}