<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação dos campos comuns do veículo (tabela veiculos).
 *
 * Valida os campos principais, dimensões, status e flags.
 * Campos específicos (combustão, elétrico, híbrido, GNV, opcionais)
 * são validados em requests separados.
 */
class VeiculoRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            // Campos obrigatórios (IDs de marca e modelo)
            'marca_id'       => 'required|integer|exists:marcas,id',
            'modelo_id'      => 'required|integer|exists:modelos,id',
            'ano_fabricacao' => 'required|integer|min:1900',
            'ano_modelo'     => 'required|integer|min:1900',
            'cor'            => 'required|max:30',
            'quilometragem'  => 'required|integer|min:0',
            'preco'          => 'required|regex:/^(\d{1,3}(\.\d{3})*|\d+)(\,\d{1,2})?$/|numeric|min:0',

            // Tipo de veículo (obrigatório para decidir o complemento)
            'tipo_veiculo'   => 'required|in:combustao,eletrico,hibrido',

            // Opcionais (comuns)
            'versao'         => 'nullable|max:50',
            'numero_portas'  => 'nullable|integer|between:2,6',
            'numero_assentos'=> 'nullable|integer|between:2,9',

            // Dimensões (opcionais)
            'comprimento_mm' => 'nullable|integer|min:0',
            'largura_mm'     => 'nullable|integer|min:0',
            'altura_mm'      => 'nullable|integer|min:0',
            'distancia_entre_eixos_mm' => 'nullable|integer|min:0',
            'peso_ordem_marcha_kg'     => 'nullable|integer|min:0',
            'volume_porta_malas_l'     => 'nullable|integer|min:0',
            'volume_cacamba_l'         => 'nullable|integer|min:0',
            'carga_util_kg'            => 'nullable|integer|min:0',
            'capacidade_reboque_kg'    => 'nullable|integer|min:0',

            // Flags e status
            'gnv_instalado'   => 'nullable|boolean',
            'status_estoque'  => 'nullable|in:disponivel,vendido,reservado',
            'status_vitrine'  => 'nullable|in:ativo,inativo',

            // Opcionais (array de IDs)
            'opcionaisIds'    => 'nullable|array',
            'opcionaisIds.*'  => 'integer|exists:opcionais,id',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            // Marca (ID)
            'marca_id.required' => 'A marca é obrigatória.',
            'marca_id.integer'  => 'A marca deve ser um número inteiro.',
            'marca_id.exists'   => 'A marca selecionada não existe.',

            // Modelo (ID)
            'modelo_id.required' => 'O modelo é obrigatório.',
            'modelo_id.integer'  => 'O modelo deve ser um número inteiro.',
            'modelo_id.exists'   => 'O modelo selecionado não existe.',

            // Ano
            'ano_fabricacao.required' => 'O ano de fabricação é obrigatório.',
            'ano_fabricacao.integer'  => 'O ano de fabricação deve ser um número inteiro.',
            'ano_fabricacao.min'      => 'O ano de fabricação deve ser maior que 1900.',
            'ano_modelo.required'     => 'O ano do modelo é obrigatório.',
            'ano_modelo.integer'      => 'O ano do modelo deve ser um número inteiro.',
            'ano_modelo.min'          => 'O ano do modelo deve ser maior que 1900.',

            // Cor
            'cor.required' => 'A cor é obrigatória.',
            'cor.max'      => 'A cor deve ter no máximo :max caracteres.',

            // Quilometragem
            'quilometragem.required' => 'A quilometragem é obrigatória.',
            'quilometragem.integer'  => 'A quilometragem deve ser um número inteiro.',
            'quilometragem.min'      => 'A quilometragem não pode ser negativa.',

            // Preço
            'preco.required' => 'O preço é obrigatório.',
            'preco.numeric'  => 'O preço deve ser um número válido.',
            'preco.min'      => 'O preço não pode ser negativo.',
            'preco.regex'    => 'Formato de preço inválido. Use o formato brasileiro (ex: 1.500,50 ou 1500,50).',

            // Tipo de veículo
            'tipo_veiculo.required' => 'O tipo de veículo é obrigatório.',
            'tipo_veiculo.in'       => 'O tipo de veículo deve ser combustão, elétrico ou híbrido.',

            // Opcionais
            'versao.max'              => 'A versão deve ter no máximo :max caracteres.',
            'numero_portas.integer'   => 'O número de portas deve ser um número inteiro.',
            'numero_portas.between'   => 'O número de portas deve estar entre :min e :max.',
            'numero_assentos.integer' => 'O número de assentos deve ser um número inteiro.',
            'numero_assentos.between' => 'O número de assentos deve estar entre :min e :max.',

            // Dimensões
            'comprimento_mm.integer' => 'O comprimento deve ser um número inteiro.',
            'comprimento_mm.min'     => 'O comprimento não pode ser negativo.',
            'largura_mm.integer'     => 'A largura deve ser um número inteiro.',
            'largura_mm.min'         => 'A largura não pode ser negativa.',
            'altura_mm.integer'      => 'A altura deve ser um número inteiro.',
            'altura_mm.min'          => 'A altura não pode ser negativa.',
            'distancia_entre_eixos_mm.integer' => 'A distância entre eixos deve ser um número inteiro.',
            'distancia_entre_eixos_mm.min'     => 'A distância entre eixos não pode ser negativa.',
            'peso_ordem_marcha_kg.integer'     => 'O peso deve ser um número inteiro.',
            'peso_ordem_marcha_kg.min'         => 'O peso não pode ser negativo.',
            'volume_porta_malas_l.integer'     => 'O volume do porta-malas deve ser um número inteiro.',
            'volume_porta_malas_l.min'         => 'O volume do porta-malas não pode ser negativo.',
            'volume_cacamba_l.integer'         => 'O volume da caçamba deve ser um número inteiro.',
            'volume_cacamba_l.min'             => 'O volume da caçamba não pode ser negativo.',
            'carga_util_kg.integer'            => 'A carga útil deve ser um número inteiro.',
            'carga_util_kg.min'                => 'A carga útil não pode ser negativa.',
            'capacidade_reboque_kg.integer'    => 'A capacidade de reboque deve ser um número inteiro.',
            'capacidade_reboque_kg.min'        => 'A capacidade de reboque não pode ser negativa.',

            // Flags
            'gnv_instalado.boolean' => 'O campo GNV instalado deve ser verdadeiro ou falso.',

            // Status
            'status_estoque.in' => 'O status de estoque deve ser disponível, vendido ou reservado.',
            'status_vitrine.in' => 'O status da vitrine deve ser ativo ou inativo.',

            // Opcionais (array)
            'opcionaisIds.array'    => 'A lista de opcionais deve ser um array.',
            'opcionaisIds.*.integer' => 'Cada ID de opcional deve ser um número inteiro.',
            'opcionaisIds.*.exists'  => 'Um ou mais opcionais selecionados não existem.',
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

        // Normaliza preco: remove pontos de milhar e converte vírgula decimal para ponto
        if (isset($data['preco']) && is_string($data['preco'])) {
            $data['preco'] = str_replace('.', '', $data['preco']); // remove pontos de milhar
            $data['preco'] = str_replace(',', '.', $data['preco']); // vírgula -> ponto
        }

        // Normaliza gnv_instalado para 0 ou 1
        if (isset($data['gnv_instalado'])) {
            $data['gnv_instalado'] = (int) (bool) $data['gnv_instalado'];
        }

        // Normaliza status_estoque e status_vitrine (garantir valores válidos)
        if (isset($data['status_estoque']) && !in_array($data['status_estoque'], ['disponivel', 'vendido', 'reservado'], true)) {
            $data['status_estoque'] = 'disponivel';
        }

        if (isset($data['status_vitrine']) && !in_array($data['status_vitrine'], ['ativo', 'inativo'], true)) {
            $data['status_vitrine'] = 'inativo';
        }

        return $data;
    }

    /**
     * Retorna os dados da tabela veiculos já validados e sanitizados.
     *
     * @return array
     */
    public function getDadosPrincipais(): array
    {
        $validated = $this->validated();

        return [
            'marca_id'              => $validated['marca_id'] ?? null,
            'modelo_id'             => $validated['modelo_id'] ?? null,
            'versao'                => $validated['versao'] ?? null,
            'ano_fabricacao'        => $validated['ano_fabricacao'] ?? null,
            'ano_modelo'            => $validated['ano_modelo'] ?? null,
            'cor'                   => $validated['cor'] ?? '',
            'quilometragem'         => $validated['quilometragem'] ?? 0,
            'preco'                 => (float) ($validated['preco'] ?? 0),
            'numero_portas'         => $validated['numero_portas'] ?? null,
            'numero_assentos'       => $validated['numero_assentos'] ?? null,
            'comprimento_mm'        => $validated['comprimento_mm'] ?? null,
            'largura_mm'            => $validated['largura_mm'] ?? null,
            'altura_mm'             => $validated['altura_mm'] ?? null,
            'distancia_entre_eixos_mm' => $validated['distancia_entre_eixos_mm'] ?? null,
            'peso_ordem_marcha_kg'  => $validated['peso_ordem_marcha_kg'] ?? null,
            'volume_porta_malas_l'  => $validated['volume_porta_malas_l'] ?? null,
            'volume_cacamba_l'      => $validated['volume_cacamba_l'] ?? null,
            'carga_util_kg'         => $validated['carga_util_kg'] ?? null,
            'capacidade_reboque_kg' => $validated['capacidade_reboque_kg'] ?? null,
            'gnv_instalado'         => $validated['gnv_instalado'] ?? 0,
            'status_estoque'        => $validated['status_estoque'] ?? 'disponivel',
            'status_vitrine'        => $validated['status_vitrine'] ?? 'inativo',
        ];
    }

    /**
     * Retorna o tipo de veículo selecionado.
     *
     * @return string
     */
    public function getTipoVeiculo(): string
    {
        $validated = $this->validated();
        return $validated['tipo_veiculo'] ?? 'combustao';
    }

    /**
     * Verifica se o veículo possui GNV instalado.
     *
     * @return bool
     */
    public function hasGNV(): bool
    {
        $validated = $this->validated();
        return !empty($validated['gnv_instalado']);
    }

    /**
     * Retorna os IDs de opcionais selecionados.
     *
     * @return array
     */
    public function getOpcionaisIds(): array
    {
        $validated = $this->validated();
        return $validated['opcionaisIds'] ?? [];
    }

    /**
     * Verifica se a requisição tem opcionais.
     *
     * @return bool
     */
    public function hasOpcionais(): bool
    {
        return !empty($this->getOpcionaisIds());
    }
}