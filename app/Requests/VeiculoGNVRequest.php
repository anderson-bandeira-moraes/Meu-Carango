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

            // Datas (instalação opcional, inspeção e validade obrigatórias)
            'data_instalacao'          => 'nullable|date',
            'data_inspecao'            => 'required|date',
            'data_validade_cilindro'   => 'required|date',

            // Booleanos (selo, certificado, registro)
            'selo_inmetro'     => 'required|boolean',
            'certificado_csv'  => 'required|boolean',
            'registro_detran'  => 'required|boolean',

            // Cilindro
            'capacidade_cilindro_m3' => 'required|numeric|min:0',
            'quantidade_cilindros'   => 'required|integer|min:0',
            'material_cilindro'      => 'nullable|max:20',
            'cilindro_norma'         => 'nullable|max:20',
            'localizacao_cilindro'   => 'nullable|max:30',

            // Consumo (opcionais)
            'consumo_cidade_m3km'   => 'nullable|numeric|min:0',
            'consumo_estrada_m3km'  => 'nullable|numeric|min:0',

            // Autonomia (opcionais)
            'autonomia_media_km'    => 'nullable|integer|min:0',
            'autonomia_cidade_km'   => 'nullable|integer|min:0',
            'autonomia_estrada_km'  => 'nullable|integer|min:0',

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
            'data_instalacao.date' => 'A data de instalação deve ser uma data válida.',
            'data_inspecao.required' => 'A data da última inspeção é obrigatória.',
            'data_inspecao.date'     => 'A data da inspeção deve ser uma data válida.',
            'data_validade_cilindro.required' => 'A data de validade do cilindro é obrigatória.',
            'data_validade_cilindro.date'     => 'A data de validade do cilindro deve ser uma data válida.',

            // Booleanos
            'selo_inmetro.required' => 'O campo selo Inmetro é obrigatório.',
            'selo_inmetro.boolean'  => 'O selo Inmetro deve ser verdadeiro ou falso.',
            'certificado_csv.required' => 'O campo certificado CSV é obrigatório.',
            'certificado_csv.boolean'  => 'O certificado CSV deve ser verdadeiro ou falso.',
            'registro_detran.required' => 'O campo registro no Detran é obrigatório.',
            'registro_detran.boolean'  => 'O registro no Detran deve ser verdadeiro ou falso.',

            // Cilindro
            'capacidade_cilindro_m3.required' => 'A capacidade do cilindro em m³ é obrigatória.',
            'capacidade_cilindro_m3.numeric'  => 'A capacidade do cilindro deve ser um número válido.',
            'capacidade_cilindro_m3.min'      => 'A capacidade do cilindro não pode ser negativa.',
            'quantidade_cilindros.required'   => 'A quantidade de cilindros é obrigatória.',
            'quantidade_cilindros.integer'    => 'A quantidade de cilindros deve ser um número inteiro.',
            'quantidade_cilindros.min'        => 'A quantidade de cilindros não pode ser negativa.',
            'material_cilindro.max'  => 'O material do cilindro deve ter no máximo :max caracteres.',
            'cilindro_norma.max'     => 'A norma do cilindro deve ter no máximo :max caracteres.',
            'localizacao_cilindro.max' => 'A localização do cilindro deve ter no máximo :max caracteres.',

            // Consumo
            'consumo_cidade_m3km.numeric' => 'O consumo na cidade em m³/km deve ser um número válido.',
            'consumo_cidade_m3km.min'     => 'O consumo na cidade em m³/km não pode ser negativo.',
            'consumo_estrada_m3km.numeric' => 'O consumo na estrada em m³/km deve ser um número válido.',
            'consumo_estrada_m3km.min'     => 'O consumo na estrada em m³/km não pode ser negativo.',

            // Autonomia
            'autonomia_media_km.integer'  => 'A autonomia média deve ser um número inteiro.',
            'autonomia_media_km.min'      => 'A autonomia média não pode ser negativa.',
            'autonomia_cidade_km.integer' => 'A autonomia na cidade deve ser um número inteiro.',
            'autonomia_cidade_km.min'     => 'A autonomia na cidade não pode ser negativa.',
            'autonomia_estrada_km.integer' => 'A autonomia na estrada deve ser um número inteiro.',
            'autonomia_estrada_km.min'     => 'A autonomia na estrada não pode ser negativa.',

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

        // Converte booleanos para 0 ou 1
        $boolFields = ['selo_inmetro', 'certificado_csv', 'registro_detran'];
        foreach ($boolFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) (bool) $data[$field];
            }
        }

        // Converte datas do formato brasileiro (dd/mm/aaaa) para Y-m-d
        $dateFields = ['data_instalacao', 'data_inspecao', 'data_validade_cilindro'];
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && $data[$field] !== '') {
                // Tenta converter se estiver no formato dd/mm/aaaa
                $parts = explode('/', $data[$field]);
                if (count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2])) {
                    $data[$field] = sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
                }
            }
        }

        // Converte campos numéricos para float/int
        $floatFields = [
            'capacidade_cilindro_m3',
            'consumo_cidade_m3km',
            'consumo_estrada_m3km',
        ];
        foreach ($floatFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }

        $intFields = [
            'quantidade_cilindros',
            'autonomia_media_km',
            'autonomia_cidade_km',
            'autonomia_estrada_km',
        ];
        foreach ($intFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (int) $data[$field];
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
        $validated = $this->validated();

        return [
            'tipo_sistema'               => $validated['tipo_sistema'] ?? null,
            'geracao_kit'                => $validated['geracao_kit'] ?? null,
            'marca_kit'                  => $validated['marca_kit'] ?? null,
            'data_instalacao'            => $validated['data_instalacao'] ?? null,
            'data_inspecao'              => $validated['data_inspecao'] ?? null,
            'data_validade_cilindro'     => $validated['data_validade_cilindro'] ?? null,
            'selo_inmetro'               => $validated['selo_inmetro'] ?? 0,
            'capacidade_cilindro_m3'     => $validated['capacidade_cilindro_m3'] ?? null,
            'quantidade_cilindros'       => $validated['quantidade_cilindros'] ?? null,
            'material_cilindro'          => $validated['material_cilindro'] ?? null,
            'cilindro_norma'             => $validated['cilindro_norma'] ?? null,
            'localizacao_cilindro'       => $validated['localizacao_cilindro'] ?? null,
            'consumo_cidade_m3km'        => $validated['consumo_cidade_m3km'] ?? null,
            'consumo_estrada_m3km'       => $validated['consumo_estrada_m3km'] ?? null,
            'autonomia_media_km'         => $validated['autonomia_media_km'] ?? null,
            'autonomia_cidade_km'        => $validated['autonomia_cidade_km'] ?? null,
            'autonomia_estrada_km'       => $validated['autonomia_estrada_km'] ?? null,
            'certificado_csv'            => $validated['certificado_csv'] ?? 0,
            'registro_detran'            => $validated['registro_detran'] ?? 0,
            'instaladora_certificada'    => $validated['instaladora_certificada'] ?? null,
            'observacoes'                => $validated['observacoes'] ?? null,
        ];
    }
}