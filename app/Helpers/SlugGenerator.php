<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Gerador de slugs descritivos para URLs de veículos.
 * 
 * Esta classe normaliza a string a partir de marca, modelo e ano,
 * removendo acentos, caracteres especiais e formatando para uso em URL.
 */
class SlugGenerator
{
    /**
     * Mapeamento de caracteres acentuados para equivalentes ASCII.
     */
    private const ACCENT_MAP = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c',
        'ñ' => 'n',
    ];

    /**
     * Gera um slug descritivo no formato marca-modelo-ano.
     *
     * @param string $marca
     * @param string $modelo
     * @param int $ano (ano_modelo)
     * @return string Ex: "fiat-palio-2023"
     */
    public static function generate(string $marca, string $modelo, int $ano): string
    {
        // Remove acentos e normaliza
        $marcaNormalizada = self::normalizarTexto($marca);
        $modeloNormalizado = self::normalizarTexto($modelo);

        // Junta partes com hífen
        $slug = $marcaNormalizada . '-' . $modeloNormalizado . '-' . $ano;

        // Reduz hífens múltiplos a apenas um
        $slug = preg_replace('/-+/', '-', $slug);

        // Remove hífens no início ou final
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Normaliza um texto: minúsculas, remove acentos, substitui espaços por hífen,
     * e remove caracteres não alfanuméricos (exceto hífen).
     *
     * @param string $texto
     * @return string
     */
    private static function normalizarTexto(string $texto): string
    {
        // Remove acentos
        $texto = strtr(mb_strtolower($texto, 'UTF-8'), self::ACCENT_MAP);

        // Substitui espaços por hífen
        $texto = str_replace(' ', '-', $texto);

        // Remove caracteres inválidos (mantém apenas a-z, 0-9 e hífen)
        $texto = preg_replace('/[^a-z0-9-]/', '', $texto);

        return $texto;
    }

    /**
     * Gera um slug a partir de um texto qualquer (ex: nome de modelo).
     *
     * @param string $texto
     * @return string
     */
    public static function fromString(string $texto): string
    {
        $slug = self::normalizarTexto($texto);
        return $slug;
    }
}