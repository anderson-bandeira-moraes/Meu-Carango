<?php

/*
|--------------------------------------------------------------------------
| Configurações do módulo de veículos
|--------------------------------------------------------------------------
|
| Este arquivo contém listas e configurações globais usadas no cadastro
| e edição de veículos, centralizando dados que podem ser reutilizados
| em diferentes partes do sistema (views, helpers, validações, etc.).
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Lista de cilindradas (motorizações) para veículos a combustão
    |--------------------------------------------------------------------------
    |
    | Valores comuns de motorizações encontradas no mercado brasileiro.
    | Usado no formulário de cadastro/edição de veículos (campo motor_tipo).
    | A lista pode ser expandida conforme necessidade, e a opção "Outro"
    | permite valores personalizados não listados.
    |
    | Referência: motores populares em veículos nacionais e importados.
    |
    */
    'motorizacoes' => [
        '1.0',
        '1.3',
        '1.4',
        '1.6',
        '1.8',
        '2.0',
        '2.2',
        '2.4',
        '2.5',
        '2.7',
        '3.0',
    ],
];