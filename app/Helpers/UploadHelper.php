<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper para upload de arquivos, com geração de nomes únicos e movimentação segura.
 *
 * Valida o MIME real via magic bytes e deriva a extensão final do MIME (nunca do nome original),
 * impedindo que arquivos executáveis sejam gravados mesmo quando renomeados.
 */
class UploadHelper
{
    /**
     * MIME types aceitos => extensão segura a usar no arquivo final.
     */
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Tamanho máximo aceito por arquivo (2 MB).
     */
    private const MAX_BYTES = 2 * 1024 * 1024;

    /**
     * Move um arquivo enviado para o diretório de uploads e retorna o caminho relativo.
     *
     * @param array  $file   O arquivo do array $_FILES (deve conter name, tmp_name, error, size).
     * @param string $subdir Subdiretório dentro de storage/uploads/ (ex: 'veiculos/hash_id').
     * @return string|false Caminho relativo (ex: 'veiculos/hash_id/arquivo.webp') ou false em erro.
     */
    public static function upload(array $file, string $subdir): string|false
    {
        // 1. Chaves necessárias
        if (!isset($file['error'], $file['tmp_name'], $file['size'])) {
            return false;
        }

        // 2. Erro de upload do PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // 3. Limite de tamanho
        if ((int) $file['size'] > self::MAX_BYTES) {
            return false;
        }

        // 4. MIME real via magic bytes (não confia em extensão nem em $file['type'])
        $mime = mime_content_type($file['tmp_name']);
        if ($mime === false || !isset(self::ALLOWED_MIMES[$mime])) {
            return false;
        }

        // 5. Extensão derivada do MIME validado
        $extensao = self::ALLOWED_MIMES[$mime];

        // 6. Prepara destino
        $basePath   = ROOT_DIR . '/storage/uploads/';
        $destinoDir = $basePath . $subdir . '/';

        if (!is_dir($destinoDir)) {
            if (!mkdir($destinoDir, 0755, true)) {
                return false;
            }
        }

        // 7. Nome único
        $nomeUnico        = self::gerarNomeUnico($extensao);
        $caminhoRelativo  = $subdir . '/' . $nomeUnico;
        $caminhoAbsoluto  = $destinoDir . $nomeUnico;

        // 8. Move
        if (!move_uploaded_file($file['tmp_name'], $caminhoAbsoluto)) {
            return false;
        }

        return $caminhoRelativo;
    }

    /**
     * Gera um nome único para o arquivo no formato timestamp_hash.extensao.
     *
     * @param string $extensao Extensão já validada (sem ponto).
     * @return string
     */
    private static function gerarNomeUnico(string $extensao): string
    {
        $timestamp = time();
        $hash = bin2hex(random_bytes(8));
        return $timestamp . '_' . $hash . '.' . $extensao;
    }
}