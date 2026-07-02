<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper para upload de arquivos, com geração de nomes únicos e movimentação segura.
 *
 * Esta classe é responsável por gerenciar o envio de arquivos para o sistema,
 * garantindo que sejam armazenados em pastas organizadas e com nomes únicos.
 * Segue o padrão dos demais helpers (SlugGenerator, RandomGenerator) com métodos estáticos.
 */
class UploadHelper
{
    /**
     * Move um arquivo enviado para o diretório de uploads e retorna o caminho relativo.
     *
     * @param array $file O arquivo do array $_FILES (deve conter name, tmp_name, error, type, size).
     * @param string $subdir Subdiretório dentro de storage/uploads/ (ex: 'veiculos/hash_id').
     * @return string|false Caminho relativo (ex: 'veiculos/hash_id/arquivo.jpg') ou false em erro.
     */
    public static function upload(array $file, string $subdir): string|false
    {
        // Validação básica do upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $basePath = ROOT_DIR . '/storage/uploads/';
        $destinoDir = $basePath . $subdir . '/';

        // Cria o diretório se não existir
        if (!is_dir($destinoDir)) {
            if (!mkdir($destinoDir, 0755, true)) {
                return false;
            }
        }

        // Gera nome único
        $nomeUnico = self::gerarNomeUnico($file['name']);
        $caminhoRelativo = $subdir . '/' . $nomeUnico;
        $caminhoAbsoluto = $destinoDir . $nomeUnico;

        // Move o arquivo
        if (!move_uploaded_file($file['tmp_name'], $caminhoAbsoluto)) {
            return false;
        }

        return $caminhoRelativo;
    }

    /**
     * Gera um nome único para o arquivo baseado no nome original.
     * Formato: timestamp_hash.extensao
     *
     * @param string $nomeOriginal
     * @return string
     */
    private static function gerarNomeUnico(string $nomeOriginal): string
    {
        $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
        $timestamp = time();
        $hash = bin2hex(random_bytes(8));
        return $timestamp . '_' . $hash . '.' . $extensao;
    }
}