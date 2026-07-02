<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação dos IDs de opcionais selecionados.
 *
 * Valida que opcionaisIds seja um array de inteiros existentes na tabela opcionais.
 * O campo é opcional (lojista pode não selecionar nenhum opcional).
 */
class VeiculoOpcionalRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            'opcionaisIds'   => 'nullable|array',
            'opcionaisIds.*' => 'integer|exists:opcionais,id',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            'opcionaisIds.array'    => 'A lista de opcionais deve ser um array.',
            'opcionaisIds.*.integer' => 'Cada ID de opcional deve ser um número inteiro.',
            'opcionaisIds.*.exists'  => 'Um ou mais opcionais selecionados não existem.',
        ];
    }

    /**
     * Sobrescreve a sanitização para garantir que seja um array de inteiros sem duplicatas.
     *
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data): array
    {
        $data = parent::sanitize($data);

        if (isset($data['opcionaisIds']) && is_array($data['opcionaisIds'])) {
            // Garante que todos os valores sejam inteiros
            $data['opcionaisIds'] = array_map('intval', $data['opcionaisIds']);
            // Remove duplicatas
            $data['opcionaisIds'] = array_unique($data['opcionaisIds']);
            // Reordena os índices
            $data['opcionaisIds'] = array_values($data['opcionaisIds']);
        }

        return $data;
    }

    /**
     * Retorna os IDs de opcionais selecionados já validados e sanitizados.
     *
     * @return array
     */
    public function getOpcionaisIds(): array
    {
        $validated = $this->validated();
        return $validated['opcionaisIds'] ?? [];
    }

    /**
     * Verifica se a requisição contém opcionais selecionados.
     *
     * @return bool
     */
    public function hasOpcionais(): bool
    {
        return !empty($this->getOpcionaisIds());
    }
}