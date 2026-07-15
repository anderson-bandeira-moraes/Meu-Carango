<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação dos campos específicos de veículos a combustão.
 *
 * Valida os campos da tabela veiculo_combustao.
 * A validação condicional garante que campos de etanol sejam obrigatórios
 * apenas quando o combustível for 'flex'.
 */
class VeiculoCombustaoRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            // Combustível (ENUM)
            'combustivel' => 'required|in:alcool,diesel,flex,gasolina',

            // Motor
            'motor_tipo'  => 'required|max:40',

            // Potência e torque (principais)
            'potencia_cv'   => 'required|integer|min_num:0',
            'torque_kgfm'   => 'nullable|numeric|min_num:0',

            // Tração
            'tracao_tipo'   => 'required|max:10',

            // Consumo (cidade obrigatório, estrada opcional)
            'consumo_cidade_kml'   => 'required|numeric|min_num:0',
            'consumo_estrada_kml'  => 'required|numeric|min_num:0',
            'consumo_medio_kml'    => 'nullable|numeric|min_num:0',

            // Tanque
            'capacidade_tanque_l' => 'required|integer|min_num:0',

            // Transmissão
            'transmissao_tipo' => 'required|max:30',
            'numero_marchas'   => 'nullable|integer|min_num:0',

            // Campos para Flex (condicionais, mas com regras básicas)
            'potencia_etanol_cv'          => 'nullable|integer|min_num:0',
            'torque_etanol_kgfm'          => 'nullable|numeric|min_num:0',
            'consumo_cidade_etanol_kml'   => 'nullable|numeric|min_num:0',
            'consumo_estrada_etanol_kml'  => 'nullable|numeric|min_num:0',
            'consumo_medio_etanol_kml'    => 'nullable|numeric|min_num:0', 

            // Campos de desempenho (opcionais)
            'regime_potencia_rpm'  => 'nullable|integer|min_num:0',
            'regime_torque_rpm'    => 'nullable|integer|min_num:0',
            'aceleracao_0_100_seg' => 'nullable|numeric|min_num:0',
            'velocidade_max_kmh'   => 'nullable|integer|min_num:0',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            // Combustível
            'combustivel.required' => 'O tipo de combustível é obrigatório.',
            'combustivel.in'       => 'O tipo de combustível deve ser: álcool, diesel, flex ou gasolina',

            // Motor
            'motor_tipo.required' => 'O tipo do motor é obrigatório.',
            'motor_tipo.max'      => 'O tipo do motor deve ter no máximo :max caracteres.',

            // Potência
            'potencia_cv.required'    => 'A potência é obrigatória.',
            'potencia_cv.integer'     => 'A potência deve ser um número inteiro.',
            'potencia_cv.min_num'     => 'A potência não pode ser negativa.',

            // Torque
            'torque_kgfm.numeric'     => 'O torque deve ser um número válido.',
            'torque_kgfm.min_num'     => 'O torque não pode ser negativo.',

            // Tração
            'tracao_tipo.required' => 'O tipo de tração é obrigatório.',
            'tracao_tipo.max'      => 'O tipo de tração deve ter no máximo :max caracteres.',

            // Consumo cidade
            'consumo_cidade_kml.required' => 'O consumo na cidade é obrigatório.',
            'consumo_cidade_kml.numeric'  => 'O consumo na cidade deve ser um número válido.',
            'consumo_cidade_kml.min_num'  => 'O consumo na cidade não pode ser negativo.',

            // Consumo estrada
            'consumo_estrada_kml.numeric' => 'O consumo na estrada deve ser um número válido.',
            'consumo_estrada_kml.min_num' => 'O consumo na estrada não pode ser negativo.',

            // Consumo médio
            'consumo_medio_kml.numeric' => 'O consumo médio deve ser um número válido.',
            'consumo_medio_kml.min_num' => 'O consumo médio não pode ser negativo.',
            'consumo_medio_etanol_kml.numeric' => 'O consumo médio com etanol deve ser um número válido.',
            'consumo_medio_etanol_kml.min_num' => 'O consumo médio com etanol não pode ser negativo.',

            // Tanque
            'capacidade_tanque_l.required' => 'A capacidade do tanque é obrigatória.',
            'capacidade_tanque_l.integer'  => 'A capacidade do tanque deve ser um número inteiro.',
            'capacidade_tanque_l.min_num'  => 'A capacidade do tanque não pode ser negativa.',

            // Transmissão
            'transmissao_tipo.required' => 'O tipo de transmissão é obrigatório.',
            'transmissao_tipo.max'      => 'O tipo de transmissão deve ter no máximo :max caracteres.',
            'numero_marchas.integer'    => 'O número de marchas deve ser um número inteiro.',
            'numero_marchas.min_num'    => 'O número de marchas não pode ser negativo.',

            // Campos Flex (condicionais)
            'potencia_etanol_cv.integer' => 'A potência com etanol deve ser um número inteiro.',
            'potencia_etanol_cv.min_num' => 'A potência com etanol não pode ser negativa.',
            'torque_etanol_kgfm.numeric' => 'O torque com etanol deve ser um número válido.',
            'torque_etanol_kgfm.min_num' => 'O torque com etanol não pode ser negativo.',
            'consumo_cidade_etanol_kml.numeric' => 'O consumo na cidade com etanol deve ser um número válido.',
            'consumo_cidade_etanol_kml.min_num' => 'O consumo na cidade com etanol não pode ser negativo.',
            'consumo_estrada_etanol_kml.numeric' => 'O consumo na estrada com etanol deve ser um número válido.',
            'consumo_estrada_etanol_kml.min_num' => 'O consumo na estrada com etanol não pode ser negativo.',

            // Desempenho
            'regime_potencia_rpm.integer' => 'O regime de potência deve ser um número inteiro.',
            'regime_potencia_rpm.min_num' => 'O regime de potência não pode ser negativo.',
            'regime_torque_rpm.integer'   => 'O regime de torque deve ser um número inteiro.',
            'regime_torque_rpm.min_num'   => 'O regime de torque não pode ser negativo.',
            'aceleracao_0_100_seg.numeric' => 'A aceleração 0-100 deve ser um número válido.',
            'aceleracao_0_100_seg.min_num' => 'A aceleração 0-100 não pode ser negativa.',
            'velocidade_max_kmh.integer'   => 'A velocidade máxima deve ser um número inteiro.',
            'velocidade_max_kmh.min_num'   => 'A velocidade máxima não pode ser negativa.',
        ];
    }

    /**
     * Sobrescreve a validação para incluir regras condicionais.
     * Campos de etanol são obrigatórios apenas se combustivel = flex.
     *
     * @return bool
     */
    public function validate(): bool
    {
        // 1. Validação padrão
        $valid = parent::validate();
        if (!$valid) {
            return false;
        }

        // 2. Validação condicional: Flex
        $data = $this->validated();
        $combustivel = $data['combustivel'] ?? null;

        if ($combustivel === 'flex') {
            $camposFlex = [
                'potencia_etanol_cv'         => 'potência com etanol',
                'consumo_cidade_etanol_kml'  => 'consumo na cidade com etanol',
                'consumo_estrada_etanol_kml'   => 'consumo na estrada com etanol',
            ];

            foreach ($camposFlex as $campo => $nome) {
                // Verifica se o campo está ausente ou vazio (permite 0)
                if (!isset($data[$campo]) || $data[$campo] === '' || $data[$campo] === null) {
                    if (!isset($this->errors[$campo])) {
                        $this->errors[$campo] = [];
                    }
                    $this->errors[$campo][] = "O campo '{$nome}' é obrigatório para veículos flex.";
                }
            }
        }

        return empty($this->errors);
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
        $floatFields = ['torque_kgfm', 'torque_etanol_kgfm', 'consumo_cidade_kml', 'consumo_estrada_kml', 'consumo_medio_kml', 'consumo_cidade_etanol_kml', 'consumo_estrada_etanol_kml', 'consumo_medio_etanol_kml', 'aceleracao_0_100_seg'];
        foreach ($floatFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }

        $intFields = ['potencia_cv', 'potencia_etanol_cv', 'capacidade_tanque_l', 'numero_marchas', 'regime_potencia_rpm', 'regime_torque_rpm', 'velocidade_max_kmh'];
        foreach ($intFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        return $data;
    }

    /**
     * Retorna os dados da tabela veiculo_combustao já validados e sanitizados.
     *
     * @return array
     */
    public function getDadosCombustao(): array
    {
        return $this->validated();
    }
}