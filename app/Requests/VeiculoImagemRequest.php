<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;

/**
 * FormRequest para validação dos campos de imagem de veículos.
 *
 * Valida upload de imagens (capa e slide), com regras específicas:
 * - Todas as imagens: WebP, até 1 MB.
 * - Imagem de capa (identificada por capa_index): além das regras acima,
 *   deve ter no máximo 300 KB (validação extra).
 * - Suporta criação (imagens + capa_index) e edição (ids_manter + novas + capa_id).
 */
class VeiculoImagemRequest extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            // Criação: imagens e capa
            'imagens'      => 'nullable|array|max:16',
            'imagens.*'    => 'file|mimes:webp|max:1024', // 1 MB

            // Índice da capa (criação)
            'capa_index'   => 'nullable|integer|min:0|max:15',

            // Edição: IDs a manter
            'ids_manter'   => 'nullable|array',
            'ids_manter.*' => 'integer|exists:veiculo_imagens,id',

            // Edição: novas imagens
            'novas'        => 'nullable|array|max:16',
            'novas.*'      => 'file|mimes:webp|max:1024', // 1 MB

            // Edição: ID da capa
            'capa_id'      => 'nullable|integer|exists:veiculo_imagens,id',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            // Imagens (criação)
            'imagens.array'    => 'A lista de imagens deve ser um array.',
            'imagens.max'      => 'Você pode enviar no máximo :max imagens por vez.',
            'imagens.*.file'   => 'Cada imagem deve ser um arquivo válido.',
            'imagens.*.mimes'  => 'As imagens devem estar no formato WebP.',
            'imagens.*.max'    => 'Cada imagem deve ter no máximo 1 MB.',

            // Capa (criação)
            'capa_index.integer' => 'O índice da capa deve ser um número inteiro.',
            'capa_index.min'     => 'O índice da capa não pode ser negativo.',
            'capa_index.max'     => 'O índice da capa não pode ultrapassar :max.',

            // IDs a manter (edição)
            'ids_manter.array'    => 'A lista de IDs a manter deve ser um array.',
            'ids_manter.*.integer' => 'Cada ID deve ser um número inteiro.',
            'ids_manter.*.exists'  => 'Um ou mais IDs de imagens não existem.',

            // Novas imagens (edição)
            'novas.array'    => 'A lista de novas imagens deve ser um array.',
            'novas.max'      => 'Você pode enviar no máximo :max novas imagens por vez.',
            'novas.*.file'   => 'Cada nova imagem deve ser um arquivo válido.',
            'novas.*.mimes'  => 'As novas imagens devem estar no formato WebP.',
            'novas.*.max'    => 'Cada nova imagem deve ter no máximo 1 MB.',

            // Capa (edição)
            'capa_id.integer' => 'O ID da capa deve ser um número inteiro.',
            'capa_id.exists'  => 'O ID da capa não corresponde a uma imagem existente.',
        ];
    }

    /**
     * Sobrescreve a validação para incluir validação extra de tamanho da capa.
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

        // 2. Validação extra: tamanho da capa (≤ 300 KB)
        $data = $this->validated();
        $capaIndex = $data['capa_index'] ?? null;

        // Verifica se há imagem de capa (capa_index presente e dentro do array de imagens)
        if ($capaIndex !== null && isset($_FILES['imagens']['tmp_name'][$capaIndex])) {
            $caminhoTemporario = $_FILES['imagens']['tmp_name'][$capaIndex];
            $tamanhoBytes = filesize($caminhoTemporario);
            $tamanhoKB = $tamanhoBytes / 1024;

            if ($tamanhoKB > 300) {
                $this->addError('capa_index', 'A imagem de capa deve ter no máximo 300 KB.');
                return false;
            }
        }

        return true;
    }

    /**
     * Sobrescreve a sanitização para normalizar campos específicos.
     *
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data): array
    {
        $data = parent::sanitize($data);

        // Normaliza capa_index para inteiro
        if (isset($data['capa_index'])) {
            $data['capa_index'] = (int) $data['capa_index'];
        }

        // Normaliza capa_id para inteiro
        if (isset($data['capa_id'])) {
            $data['capa_id'] = (int) $data['capa_id'];
        }

        // Normaliza ids_manter: array de inteiros, sem duplicatas, reindexado
        if (isset($data['ids_manter']) && is_array($data['ids_manter'])) {
            $data['ids_manter'] = array_map('intval', $data['ids_manter']);
            $data['ids_manter'] = array_unique($data['ids_manter']);
            $data['ids_manter'] = array_values($data['ids_manter']);
        }

        return $data;
    }

    /**
     * Retorna o índice da capa (criação).
     *
     * @return int|null
     */
    public function getCapaIndex(): ?int
    {
        $validated = $this->validated();
        return $validated['capa_index'] ?? null;
    }

    /**
     * Retorna o ID da capa (edição).
     *
     * @return int|null
     */
    public function getCapaId(): ?int
    {
        $validated = $this->validated();
        return $validated['capa_id'] ?? null;
    }

    /**
     * Retorna os IDs de imagens a manter (edição).
     *
     * @return array
     */
    public function getIdsManter(): array
    {
        $validated = $this->validated();
        return $validated['ids_manter'] ?? [];
    }

    /**
     * Verifica se a requisição possui imagem de capa (criação).
     *
     * @return bool
     */
    public function hasCapa(): bool
    {
        return $this->getCapaIndex() !== null;
    }

    /**
     * Verifica se a requisição possui alterações de imagem (edição).
     *
     * @return bool
     */
    public function hasImagensAlteracao(): bool
    {
        $validated = $this->validated();
        return !empty($validated['ids_manter']) || !empty($validated['novas']);
    }
}