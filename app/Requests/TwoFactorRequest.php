<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação do código de autenticação em duas etapas (2FA).
 *
 * Valida o campo:
 * - code: obrigatório, deve ser uma string numérica de exatamente 6 dígitos.
 *
 * Fornece método auxiliar getCode() para extrair o código validado.
 */
class TwoFactorRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            'code' => 'required|numeric|min:6|max:6',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            'code.required' => 'O código de verificação é obrigatório.',
            'code.numeric'  => 'O código deve conter apenas números.',
            'code.min'      => 'O código deve ter exatamente 6 dígitos.',
            'code.max'      => 'O código deve ter exatamente 6 dígitos.',
        ];
    }

    /**
     * Retorna o código 2FA já validado e sanitizado.
     *
     * @return string O código de 6 dígitos
     */
    public function getCode(): string
    {
        $validated = $this->validated();
        return $validated['code'] ?? '';
    }

    /**
     * Sobrescreve a sanitização para garantir que o código seja uma string
     * sem espaços ou caracteres especiais.
     *
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data): array
    {
        $data = parent::sanitize($data);

        if (isset($data['code']) && is_string($data['code'])) {
            // Remove tudo que não é número (espaços, traços, etc.)
            $data['code'] = preg_replace('/[^0-9]/', '', $data['code']);
        }

        return $data;
    }
}