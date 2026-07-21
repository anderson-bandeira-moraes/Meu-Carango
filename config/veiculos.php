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

    /*
    |--------------------------------------------------------------------------
    | Cores disponíveis (nome => código hexadecimal)
    |--------------------------------------------------------------------------
    */
    'cores' => [
        'Preto'             => '#000000',
        'Branco'            => '#FFFFFF',
        'Prata'             => '#C0C0C0',
        'Cinza'             => '#808080',
        'Vermelho'          => '#FF0000',
        'Azul'              => '#0000FF',
        'Verde'             => '#008000',
        'Amarelo'           => '#FFD700',
        'Laranja'           => '#FFA500',
        'Marrom'            => '#8B4513',
        'Bege'              => '#F5F5DC',
        'Dourado'           => '#FFD700',
        'Prata Metálico'    => '#A8A9AD',
        'Azul Metálico'     => '#1E3A5F',
        'Vermelho Metálico' => '#8B0000',
        'Verde Metálico'    => '#2E8B57',
        'Cinza Metálico'    => '#696969',
        'Preto Metálico'    => '#1A1A1A',
        'Branco Pérola'     => '#F8F8FF',
        'Azul Escuro'       => '#191970',
        'Vinho'             => '#722F37',
        'Bronze'            => '#CD7F32',
    ],

    /*
    |--------------------------------------------------------------------------
    | Número de portas (value => label)
    |--------------------------------------------------------------------------
    */
    'portas' => [
        2 => '2 portas',
        3 => '3 portas',
        4 => '4 portas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Número de assentos (array de valores numéricos)
    |--------------------------------------------------------------------------
    */
    'assentos' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],

    /*
    |--------------------------------------------------------------------------
    | Tipos de combustível (value => label)
    |--------------------------------------------------------------------------
    */
    'combustiveis' => [
        'alcool'   => 'Álcool',
        'diesel'   => 'Diesel',
        'flex'     => 'Flex',
        'gasolina' => 'Gasolina',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de tração (value => label)
    |--------------------------------------------------------------------------
    */
    'tracao' => [
        'dianteira' => 'Dianteira',
        'traseira'  => 'Traseira',
        'integral'  => 'Integral',
        '4x4'       => '4x4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de transmissão por contexto (value => label)
    |--------------------------------------------------------------------------
    */
    'transmissoes' => [
        'combustao' => [
            'Manual'            => 'Manual (MT)',
            'Automática'        => 'Automática Convencional (AT)',
            'Automática CVT'    => 'Automática CVT',
            'Automatizada'      => 'Automatizada (AMT)',
            'Dupla Embreagem'   => 'Dupla Embreagem (DCT)',
        ],
        'eletrico' => [
            'Automática'         => 'Automática (Padrão para elétricos)',
        ],
        'hibrido' => [
            'e-CVT'             => 'e-CVT',
            'Automática'        => 'Automática Convencional (AT)',
            'Dupla Embreagem'   => 'Dupla Embreagem (DCT)',
            'CVT'               => 'CVT',
            'Manual'            => 'Manual (MT)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Número de marchas (range de 4 a 10)
    |--------------------------------------------------------------------------
    */
    'marchas' => [4, 5, 6, 7, 8, 9, 10],

    /*
    |--------------------------------------------------------------------------
    | GNV – Tipos de sistema (value => label)
    |--------------------------------------------------------------------------
    */
    'gnv_sistemas' => [
        'GNC' => 'GNC (Gás Natural Comprimido)',
        'GLP' => 'GLP (Gás Liquefeito de Petróleo)',
    ],

    /*
    |--------------------------------------------------------------------------
    | GNV – Gerações do kit (value => label)
    |--------------------------------------------------------------------------
    */
    'gnv_geracoes' => [
        '3ª' => '3ª Geração',
        '4ª' => '4ª Geração',
        '5ª' => '5ª Geração',
        '6ª' => '6ª Geração',
    ],

    /*
    |--------------------------------------------------------------------------
    | GNV – Materiais do cilindro (value => label)
    |--------------------------------------------------------------------------
    */
    'gnv_materiais' => [
        'Aço'                          => 'Aço',
        'Alumínio'                     => 'Alumínio',
        'Compósito (Fibra de Carbono)' => 'Compósito (Fibra de Carbono)',
        'Compósito (Fibra de Vidro)'   => 'Compósito (Fibra de Vidro)',
    ],

    /*
    |--------------------------------------------------------------------------
    | GNV – Localizações do cilindro (value => label)
    |--------------------------------------------------------------------------
    */
    'gnv_localizacoes' => [
        'Porta-malas' => 'Porta-malas',
        'Sob o assoalho (Por baixo do carro)' => 'Sob o assoalho (Por baixo do carro)',
        'Atrás dos bancos' => 'Atrás dos bancos',
        'Sobre o assoalho (área de carga)' => 'Sobre o assoalho (área de carga)',
    ],

    /*
    |--------------------------------------------------------------------------
    | GNV – Capacidades do cilindro em m³ (valores numéricos)
    |--------------------------------------------------------------------------
    */
    'gnv_capacidades' => [7.5, 9.5, 10, 15, 17, 21, 24.5, 25],

    /*
    |--------------------------------------------------------------------------
    | GNV – Quantidade de cilindros (valores numéricos)
    |--------------------------------------------------------------------------
    */
    'gnv_quantidades' => [1, 2, 3, 4, 5],

    /*
    |--------------------------------------------------------------------------
    | Conectores DC para veículos elétricos (value => label)
    |--------------------------------------------------------------------------
    */
    'conectores_dc' => [
        'CCS2 (Combo 2)' => 'CCS2 (Combo 2) – Padrão Brasil/Europa',
        'CCS1 (Combo 1)' => 'CCS1 (Combo 1) – Padrão América do Norte',
        'NACS'           => 'NACS – Padrão Tesla (América do Norte)',
        'CHAdeMO'        => 'CHAdeMO – Padrão Japonês (Nissan, Mitsubishi)',
        'GB/T'           => 'GB/T – Padrão Chinês',
    ],

    /*
    |--------------------------------------------------------------------------
    | Conectores AC para veículos elétricos e PHEV (value => label)
    |--------------------------------------------------------------------------
    */
    'conectores_ac' => [
        'Tipo 2 (Mennekes)'       => 'Tipo 2 (Mennekes) – Padrão Brasil/Europa',
        'Tipo 1 (SAE J1772)'      => 'Tipo 1 (SAE J1772) – Padrão América do Norte/Japão',
        'NACS'                    => 'NACS – Padrão Tesla (América do Norte)',
        'GB/T'                    => 'GB/T – Padrão Chinês',
        'Tipo 3 (Scame)'          => 'Tipo 3 (Scame) – Padrão Europeu (França/Itália)',
        'Schuko'                  => 'Schuko – Tomada doméstica (carga lenta)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de veículos híbridos (value => label)
    |--------------------------------------------------------------------------
    */
    'tipos_hibrido' => [
        'hev'  => 'HEV',
        'mhev' => 'MHEV',
        'phev' => 'PHEV',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de bateria para veículos híbridos e elétricos (value => label)
    |--------------------------------------------------------------------------
    */
    'baterias_tipos_hibrido' => [
        'NiMH (Níquel-Hidreto Metálico)'         => 'NiMH (Níquel-Hidreto Metálico)',
        'NMC (Níquel-Manganês-Cobalto)'          => 'NMC (Níquel-Manganês-Cobalto)',
        'NCA (Níquel-Cobalto-Alumínio)'          => 'NCA (Níquel-Cobalto-Alumínio)',
        'LFP (Fosfato de Ferro e Lítio)'         => 'LFP (Fosfato de Ferro e Lítio)',
        'LMO (Óxido de Lítio e Manganês)'        => 'LMO (Óxido de Lítio e Manganês)',
        'LTO (Óxido de Lítio e Titânio)'         => 'LTO (Óxido de Lítio e Titânio)',
        'Bateria 48V'                            => 'Bateria 48V',
        'Estado Sólido'                          => 'Estado Sólido',
        'Supercapacitores'                       => 'Supercapacitores',
    ],

    'baterias_tipos_bev' => [
        'NMC (Níquel-Manganês-Cobalto)'  => 'NMC (Níquel-Manganês-Cobalto)',
        'LFP (Fosfato de Ferro e Lítio)' => 'LFP (Fosfato de Ferro e Lítio)',
        'NCA (Níquel-Cobalto-Alumínio)'  => 'NCA (Níquel-Cobalto-Alumínio)',
        'LMO (Óxido de Lítio e Manganês)' => 'LMO (Óxido de Lítio e Manganês)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tensões do sistema elétrico para veículos híbridos
    |--------------------------------------------------------------------------
    */
    'sistema_eletrico_tensoes' => [
        '12V'  => '12V',
        '48V'  => '48V',
        '300V' => '300V',
        '400V' => '400V',
        '800V' => '800V',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tensões do sistema elétrico para veículos 100% elétricos (BEV)
    |--------------------------------------------------------------------------
    */
    'sistema_eletrico_tensoes_bev' => [
        '300V'  => '300V',
        '350V'  => '350V',
        '400V'  => '400V',
        '450V'  => '450V',
        '700V'  => '700V',
        '800V'  => '800V',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status de estoque (value => label)
    |--------------------------------------------------------------------------
    */
    'status_estoque' => [
        'disponivel' => 'Disponível',
        'vendido'    => 'Vendido',
        'reservado'  => 'Reservado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status de vitrine (value => label)
    |--------------------------------------------------------------------------
    */
    'status_vitrine' => [
        'ativo'   => 'Ativo',
        'inativo' => 'Inativo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de carroceria (value => label)
    |--------------------------------------------------------------------------
    */
    'carrocerias' => [
        'hatch'        => 'Hatch',
        'sedan'        => 'Sedã',
        'suv'          => 'SUV',
        'picape'       => 'Picape',
        'perua'        => 'Perua',
        'coupe'        => 'Cupê',
        'fastback'     => 'Fastback',
        'conversivel'  => 'Conversível',
        'minivan'      => 'Minivan',
        'jipe'         => 'Jipe',
        'crossover'    => 'Crossover',
        'utilitario'   => 'Utilitário',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de direção (value => label)
    |--------------------------------------------------------------------------
    */
    'tipos_direcao' => [
        'mecanica'          => 'Mecânica',
        'hidraulica'        => 'Hidráulica',
        'eletrica'          => 'Elétrica',
        'eletro-hidraulica' => 'Eletro-hidráulica',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de roda (value => label)
    |--------------------------------------------------------------------------
    */
    'tipos_roda' => [
        'liga_leve' => 'Liga Leve',
        'calota'    => 'Calota',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de freio (value => label)
    |--------------------------------------------------------------------------
    */
    'tipos_freio' => [
        'disco'  => 'Disco',
        'tambor' => 'Tambor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Aros de roda em polegadas (value => label)
    |--------------------------------------------------------------------------
    */
    'aros_pneu' => [
        13 => '13"',
        14 => '14"',
        15 => '15"',
        16 => '16"',
        17 => '17"',
        18 => '18"',
        19 => '19"',
        20 => '20"',
        21 => '21"',
        22 => '22"',
    ],
];