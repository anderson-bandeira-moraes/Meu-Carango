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
            'Relação Única'         => 'Relação Única (Fixed-Ratio)',
            'Duas Velocidades'      => 'Duas Velocidades (2-Speed)',
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
        'Aço' => 'Aço',
        'Alumínio' => 'Alumínio',
        'Compósito (Fibra de Carbono)' => 'Compósito (Fibra de Carbono)',
        'Compósito (Fibra de Vidro)' => 'Compósito (Fibra de Vidro)',
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

    'campos' => [
        // ============================================================
        // INFORMAÇÕES BÁSICAS
        // ============================================================
        'marca_id' => [
            'label' => 'Marca',
            'secao' => 'basicas',
            'tipo'  => 'select',
        ],
        'modelo_id' => [
            'label' => 'Modelo',
            'secao' => 'basicas',
            'tipo'  => 'select',
        ],
        'versao' => [
            'label' => 'Versão',
            'secao' => 'basicas',
            'tipo'  => 'text',
        ],
        'ano_fabricacao' => [
            'label' => 'Ano de Fabricação',
            'secao' => 'basicas',
            'tipo'  => 'number',
        ],
        'ano_modelo' => [
            'label' => 'Ano do Modelo',
            'secao' => 'basicas',
            'tipo'  => 'number',
        ],
        'cor' => [
            'label' => 'Cor',
            'secao' => 'basicas',
            'tipo'  => 'text', // hidden no formulário, mas tratado com dropdown
        ],
        'quilometragem' => [
            'label' => 'Quilometragem (km)',
            'secao' => 'basicas',
            'tipo'  => 'number',
        ],
        'preco' => [
            'label' => 'Preço (R$)',
            'secao' => 'basicas',
            'tipo'  => 'text', // com máscara
            'format' => 'currency',
        ],
        'numero_portas' => [
            'label' => 'Nº de Portas',
            'secao' => 'basicas',
            'tipo'  => 'select',
        ],
        'numero_assentos' => [
            'label' => 'Nº de Assentos',
            'secao' => 'basicas',
            'tipo'  => 'select',
        ],
        'tipo_veiculo' => [
            'label' => 'Tipo de Veículo',
            'secao' => 'basicas',
            'tipo'  => 'hidden', // não mostrado, mas usado internamente
        ],
        'gnv_instalado' => [
            'label' => 'GNV instalado',
            'secao' => 'basicas',
            'tipo'  => 'checkbox',
        ],

        // ============================================================
        // DIMENSÕES E CAPACIDADES
        // ============================================================
        'comprimento_mm' => [
            'label' => 'Comprimento (mm)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],
        'largura_mm' => [
            'label' => 'Largura (mm)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],
        'altura_mm' => [
            'label' => 'Altura (mm)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],
        'distancia_entre_eixos_mm' => [
            'label' => 'Distância entre eixos (mm)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],
        'peso_ordem_marcha_kg' => [
            'label' => 'Peso em ordem de marcha (kg)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],
        'volume_porta_malas_l' => [
            'label' => 'Volume do porta-malas (L)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],
        'volume_cacamba_l' => [
            'label' => 'Volume da caçamba (L)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],
        'carga_util_kg' => [
            'label' => 'Carga útil (kg)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],
        'capacidade_reboque_kg' => [
            'label' => 'Capacidade de reboque (kg)',
            'secao' => 'dimensoes',
            'tipo'  => 'number',
        ],

        // ============================================================
        // DADOS DO MOTOR A COMBUSTÃO
        // ============================================================
        'combustivel' => [
            'label' => 'Combustível',
            'secao' => 'combustao',
            'tipo'  => 'select',
        ],
        'motor_tipo' => [
            'label' => 'Motorização',
            'secao' => 'combustao',
            'tipo'  => 'select',
        ],
        'potencia_cv' => [
            'label' => 'Potência (cv)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'torque_kgfm' => [
            'label' => 'Torque (kgfm)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'tracao_tipo' => [
            'label' => 'Tipo de Tração',
            'secao' => 'combustao',
            'tipo'  => 'select',
        ],
        'consumo_cidade_kml' => [
            'label' => 'Consumo cidade (km/l)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'consumo_estrada_kml' => [
            'label' => 'Consumo estrada (km/l)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'consumo_medio_kml' => [
            'label' => 'Consumo médio (km/l)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'capacidade_tanque_l' => [
            'label' => 'Capacidade do tanque (L)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'transmissao_tipo' => [
            'label' => 'Tipo de Transmissão',
            'secao' => 'combustao',
            'tipo'  => 'select',
        ],
        'numero_marchas' => [
            'label' => 'Nº de Marchas',
            'secao' => 'combustao',
            'tipo'  => 'select',
        ],
        // Flex
        'potencia_etanol_cv' => [
            'label' => 'Potência com etanol (cv)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'torque_etanol_kgfm' => [
            'label' => 'Torque com etanol (kgfm)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'consumo_cidade_etanol_kml' => [
            'label' => 'Consumo cidade com etanol (km/l)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'consumo_estrada_etanol_kml' => [
            'label' => 'Consumo estrada com etanol (km/l)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'consumo_medio_etanol_kml' => [
            'label' => 'Consumo médio com etanol (km/l)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        // Desempenho
        'regime_potencia_rpm' => [
            'label' => 'Regime de potência (RPM)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'regime_torque_rpm' => [
            'label' => 'Regime de torque (RPM)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'aceleracao_0_100_seg' => [
            'label' => 'Aceleração 0-100 (s)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],
        'velocidade_max_kmh' => [
            'label' => 'Velocidade máxima (km/h)',
            'secao' => 'combustao',
            'tipo'  => 'number',
        ],

        // ============================================================
        // DADOS DO VEÍCULO ELÉTRICO
        // ============================================================
        'potencia_max_cv' => [
            'label' => 'Potência máxima (cv)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'torque_max_nm' => [
            'label' => 'Torque máximo (Nm)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'torque_max_kgfm' => [
            'label' => 'Torque máximo (kgfm)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'aceleracao_0_100_seg' => [
            'label' => 'Aceleração 0-100 (s)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'velocidade_max_kmh' => [
            'label' => 'Velocidade máxima (km/h)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'capacidade_liquida_kwh' => [
            'label' => 'Capacidade líquida (kWh)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'saude_bateria_soh' => [
            'label' => 'Saúde da bateria (SoH %)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'autonomia_wltp_km' => [
            'label' => 'Autonomia WLTP (km)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'autonomia_inmetro_km' => [
            'label' => 'Autonomia Inmetro (km)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'garantia_bateria' => [
            'label' => 'Garantia da bateria',
            'secao' => 'eletrico',
            'tipo'  => 'text',
        ],
        'potencia_max_dc_kw' => [
            'label' => 'Potência máxima DC (kW)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'tipo_conector_dc' => [
            'label' => 'Tipo de conector DC',
            'secao' => 'eletrico',
            'tipo'  => 'select',
        ],
        'tipo_conector_ac' => [
            'label' => 'Tipo de conector AC',
            'secao' => 'eletrico',
            'tipo'  => 'select',
        ],
        'tempo_carga_dc_min' => [
            'label' => 'Tempo de carga DC (min)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],
        'consumo_energetico_kwh_100km' => [
            'label' => 'Consumo energético (kWh/100km)',
            'secao' => 'eletrico',
            'tipo'  => 'number',
        ],

        // ============================================================
        // DADOS DO VEÍCULO HÍBRIDO
        // ============================================================
        'tipo' => [
            'label' => 'Tipo de híbrido',
            'secao' => 'hibrido',
            'tipo'  => 'select',
        ],
        'motor_combustao_tipo' => [
            'label' => 'Motorização (combustão)',
            'secao' => 'hibrido',
            'tipo'  => 'select',
        ],
        'motor_combustao_potencia_cv' => [
            'label' => 'Potência (combustão) (cv)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'motor_combustao_torque_kgfm' => [
            'label' => 'Torque (combustão) (kgfm)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'motor_eletrico_potencia_cv' => [
            'label' => 'Potência (elétrico) (cv)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'motor_eletrico_torque_kgfm' => [
            'label' => 'Torque (elétrico) (kgfm)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'potencia_combinada_cv' => [
            'label' => 'Potência combinada (cv)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'torque_combinado_kgfm' => [
            'label' => 'Torque combinado (kgfm)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'bateria_capacidade_kwh' => [
            'label' => 'Capacidade da bateria (kWh)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'bateria_tipo' => [
            'label' => 'Tipo da bateria',
            'secao' => 'hibrido',
            'tipo'  => 'select',
        ],
        'modo_eletrico_puro' => [
            'label' => 'Modo elétrico puro',
            'secao' => 'hibrido',
            'tipo'  => 'select',
        ],
        'autonomia_eletrica_pbev_km' => [
            'label' => 'Autonomia elétrica (PBEV) (km)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'autonomia_combinada_km' => [
            'label' => 'Autonomia combinada (km)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'bateria_garantia' => [
            'label' => 'Garantia da bateria',
            'secao' => 'hibrido',
            'tipo'  => 'text',
        ],
        'carregamento_potencia_ac_kw' => [
            'label' => 'Potência de carregamento AC (kW)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'carregamento_tempo_ac_horas' => [
            'label' => 'Tempo de carregamento AC (h)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'carregamento_potencia_dc_kw' => [
            'label' => 'Potência de carregamento DC (kW)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'carregamento_tipo_conector_ac' => [
            'label' => 'Tipo de conector AC (PHEV)',
            'secao' => 'hibrido',
            'tipo'  => 'select',
        ],
        'consumo_cidade_kml' => [
            'label' => 'Consumo cidade (km/l)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'consumo_estrada_kml' => [
            'label' => 'Consumo estrada (km/l)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'consumo_medio_kml' => [
            'label' => 'Consumo médio (km/l)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'consumo_cidade_etanol_kml' => [
            'label' => 'Consumo cidade com etanol (km/l)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'consumo_estrada_etanol_kml' => [
            'label' => 'Consumo estrada com etanol (km/l)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'consumo_medio_etanol_kml' => [
            'label' => 'Consumo médio com etanol (km/l)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],
        'capacidade_tanque_l' => [
            'label' => 'Capacidade do tanque (L)',
            'secao' => 'hibrido',
            'tipo'  => 'number',
        ],

        // ============================================================
        // DADOS DO KIT GNV
        // ============================================================
        'tipo_sistema' => [
            'label' => 'Tipo de sistema GNV',
            'secao' => 'gnv',
            'tipo'  => 'select',
        ],
        'geracao_kit' => [
            'label' => 'Geração do kit',
            'secao' => 'gnv',
            'tipo'  => 'select',
        ],
        'marca_kit' => [
            'label' => 'Marca do kit',
            'secao' => 'gnv',
            'tipo'  => 'text',
        ],
        'data_instalacao' => [
            'label' => 'Data de instalação',
            'secao' => 'gnv',
            'tipo'  => 'date',
        ],
        'data_inspecao' => [
            'label' => 'Data da última inspeção',
            'secao' => 'gnv',
            'tipo'  => 'date',
        ],
        'data_validade_cilindro' => [
            'label' => 'Validade do cilindro',
            'secao' => 'gnv',
            'tipo'  => 'date',
        ],
        'possui_csv' => [
            'label' => 'Possui CSV',
            'secao' => 'gnv',
            'tipo'  => 'checkbox',
        ],
        'possui_selo_gnv' => [
            'label' => 'Possui selo GNV',
            'secao' => 'gnv',
            'tipo'  => 'checkbox',
        ],
        'capacidade_cilindro_m3' => [
            'label' => 'Capacidade do cilindro (m³)',
            'secao' => 'gnv',
            'tipo'  => 'number',
        ],
        'quantidade_cilindros' => [
            'label' => 'Quantidade de cilindros',
            'secao' => 'gnv',
            'tipo'  => 'number',
        ],
        'material_cilindro' => [
            'label' => 'Material do cilindro',
            'secao' => 'gnv',
            'tipo'  => 'select',
        ],
        'localizacao_cilindro' => [
            'label' => 'Localização do cilindro',
            'secao' => 'gnv',
            'tipo'  => 'select',
        ],
        'consumo_cidade_m3km' => [
            'label' => 'Consumo cidade (m³/km)',
            'secao' => 'gnv',
            'tipo'  => 'number',
        ],
        'consumo_estrada_m3km' => [
            'label' => 'Consumo estrada (m³/km)',
            'secao' => 'gnv',
            'tipo'  => 'number',
        ],
        'autonomia_media_km' => [
            'label' => 'Autonomia média (km)',
            'secao' => 'gnv',
            'tipo'  => 'number',
        ],
        'autonomia_cidade_km' => [
            'label' => 'Autonomia cidade (km)',
            'secao' => 'gnv',
            'tipo'  => 'number',
        ],
        'autonomia_estrada_km' => [
            'label' => 'Autonomia estrada (km)',
            'secao' => 'gnv',
            'tipo'  => 'number',
        ],
        'instaladora_certificada' => [
            'label' => 'Instaladora certificada',
            'secao' => 'gnv',
            'tipo'  => 'text',
        ],
        'observacoes' => [
            'label' => 'Observações',
            'secao' => 'gnv',
            'tipo'  => 'text',
        ],

        // ============================================================
        // OPCIONAIS (organizados por categoria)
        // ============================================================
        // Conforto
        'opcional_1' => [
            'label' => 'Ar-condicionado manual',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_2' => [
            'label' => 'Ar-condicionado digital',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_3' => [
            'label' => 'Ar-condicionado automático',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_4' => [
            'label' => 'Ar-condicionado dual-zone',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_5' => [
            'label' => 'Direção assistida hidráulica',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_6' => [
            'label' => 'Direção assistida elétrica',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_7' => [
            'label' => 'Direção assistida progressiva',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_8' => [
            'label' => 'Câmbio automático convencional',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_9' => [
            'label' => 'Câmbio automático CVT',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_10' => [
            'label' => 'Câmbio automático automatizado',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_11' => [
            'label' => 'Câmbio automático dupla embreagem',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_12' => [
            'label' => 'Vidros elétricos (dianteiros e traseiros)',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_13' => [
            'label' => 'Travas elétricas',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_14' => [
            'label' => 'Retrovisores elétricos',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_15' => [
            'label' => 'Retrovisores com seta',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_16' => [
            'label' => 'Retrovisores com rebatimento',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_17' => [
            'label' => 'Bancos de couro',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_18' => [
            'label' => 'Volante multifuncional',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_19' => [
            'label' => 'Regulagem de altura do volante',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_20' => [
            'label' => 'Regulagem de profundidade do volante',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_21' => [
            'label' => 'Chave presencial (keyless)',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],
        'opcional_22' => [
            'label' => 'Partida remota',
            'secao' => 'opcionais',
            'categoria' => 'Conforto',
        ],

        // Estética
        'opcional_40' => [
            'label' => 'Rodas de liga leve (aro 14 a 20+)',
            'secao' => 'opcionais',
            'categoria' => 'Estética',
        ],
        'opcional_41' => [
            'label' => 'Faróis de neblina (dianteiros/traseiros)',
            'secao' => 'opcionais',
            'categoria' => 'Estética',
        ],
        'opcional_42' => [
            'label' => 'Película de proteção solar',
            'secao' => 'opcionais',
            'categoria' => 'Estética',
        ],
        'opcional_43' => [
            'label' => 'Pintura metálica ou perolizada',
            'secao' => 'opcionais',
            'categoria' => 'Estética',
        ],

        // Off-Road
        'opcional_44' => [
            'label' => 'Tração 4x4 com reduzida',
            'secao' => 'opcionais',
            'categoria' => 'Off-Road',
        ],
        'opcional_45' => [
            'label' => 'Engate de reboque',
            'secao' => 'opcionais',
            'categoria' => 'Off-Road',
        ],

        // Segurança
        'opcional_23' => [
            'label' => 'Airbags frontais',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_24' => [
            'label' => 'Airbags laterais e de cortina',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_25' => [
            'label' => 'Freios ABS com EBD',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_26' => [
            'label' => 'Controle de estabilidade (ESC)',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_27' => [
            'label' => 'Controle de tração (TC)',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_28' => [
            'label' => 'Frenagem autônoma de emergência (AEB)',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_29' => [
            'label' => 'Alerta de ponto cego (BSD)',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_30' => [
            'label' => 'Sensores de estacionamento dianteiros',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_31' => [
            'label' => 'Sensores de estacionamento traseiros',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_32' => [
            'label' => 'Câmera de ré',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],
        'opcional_33' => [
            'label' => 'Monitoramento de pressão dos pneus (TPMS)',
            'secao' => 'opcionais',
            'categoria' => 'Segurança',
        ],

        // Tecnologia
        'opcional_34' => [
            'label' => 'Central Multimídia com tela touchscreen',
            'secao' => 'opcionais',
            'categoria' => 'Tecnologia',
        ],
        'opcional_35' => [
            'label' => 'Apple CarPlay (sem fio)',
            'secao' => 'opcionais',
            'categoria' => 'Tecnologia',
        ],
        'opcional_36' => [
            'label' => 'Android Auto (sem fio)',
            'secao' => 'opcionais',
            'categoria' => 'Tecnologia',
        ],
        'opcional_37' => [
            'label' => 'Bluetooth® para áudio e chamadas',
            'secao' => 'opcionais',
            'categoria' => 'Tecnologia',
        ],
        'opcional_38' => [
            'label' => 'Entrada USB (dianteira e traseira)',
            'secao' => 'opcionais',
            'categoria' => 'Tecnologia',
        ],
        'opcional_39' => [
            'label' => 'Carregador de celular por indução',
            'secao' => 'opcionais',
            'categoria' => 'Tecnologia',
        ],
    ],
];