<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para autenticação de administradores e lojistas.
 *
 * Valida os campos de login:
 * - email: obrigatório, formato de e-mail, máximo 100 caracteres
 * - senha: obrigatório, mínimo 6 caracteres
 *
 * Fornece método auxiliar getCredentials() para extrair email e senha validados.
 */
class LoginRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:100',
            'senha' => 'required|min:6',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email'    => 'Informe um endereço de e-mail válido.',
            'email.max'      => 'O e-mail deve ter no máximo :max caracteres.',
            'senha.required' => 'A senha é obrigatória.',
            'senha.min'      => 'A senha deve ter no mínimo :min caracteres.',
        ];
    }

    /**
     * Retorna as credenciais (email e senha) já validadas e sanitizadas.
     * Útil para repassar diretamente ao serviço de autenticação.
     *
     * @return array{email: string, senha: string}
     */
    public function getCredentials(): array
    {
        $validated = $this->validated();
        return [
            'email' => $validated['email'] ?? '',
            'senha' => $validated['senha'] ?? '',
        ];
    }

    /**
     * Sobrescreve a sanitização para garantir que o email seja convertido para minúsculas.
     * A classe base já aplica trim() e converte null para ''.
     *
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data): array
    {
        $data = parent::sanitize($data);

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }

        return $data;
    }
}