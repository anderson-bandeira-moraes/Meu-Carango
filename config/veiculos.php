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

    /*
    |--------------------------------------------------------------------------
    | Regras condicionais para veículos híbridos (HEV, MHEV, PHEV)
    |--------------------------------------------------------------------------
    |
    | Define o comportamento de exibição e valores forçados para campos
    | específicos, de acordo com o tipo de híbrido selecionado.
    |
    | Estrutura:
    |   'tipo' => [
    |       'modo_eletrico_puro' => [
    |           'visivel'      => bool,   // Se o campo deve ser exibido
    |           'forcar_valor' => int|null // Valor a ser forçado (0 ou 1), ou null para livre
    |       ],
    |       'campos_phev' => [
    |           'visivel' => bool // Se os campos de carregamento e autonomia elétrica devem ser exibidos
    |       ]
    |   ]
    |
    | Tipos suportados:
    |   - hev  : Híbrido convencional (ex: Toyota Corolla Hybrid)
    |   - mhev : Híbrido leve 48V (ex: Jeep Compass T270)
    |   - phev : Híbrido plug-in (ex: BYD King, Haval H6)
    |
    */
    'regras_hibrido' => [
        'hev' => [
            'modo_eletrico_puro' => [
                'visivel'      => true,
                'forcar_valor' => null, // permite 0 ou 1 (alguns HEV têm modo EV limitado)
            ],
            'campos_phev' => [
                'visivel' => false, // carregamento e autonomia elétrica NÃO se aplicam a HEV
            ],
        ],
        'mhev' => [
            'modo_eletrico_puro' => [
                'visivel'      => false, // MHEV não possui modo elétrico puro
                'forcar_valor' => 0,     // força 0 (não tem)
            ],
            'campos_phev' => [
                'visivel' => false, // carregamento e autonomia elétrica NÃO se aplicam a MHEV
            ],
        ],
        'phev' => [
            'modo_eletrico_puro' => [
                'visivel'      => true,  // PHEV possui modo elétrico puro
                'forcar_valor' => 1,     // força 1 (sempre tem)
            ],
            'campos_phev' => [
                'visivel' => true, // carregamento e autonomia elétrica são essenciais para PHEV
            ],
        ],
    ],
];