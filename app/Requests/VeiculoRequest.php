<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\FormRequest;
use App\Repository\MarcaRepository;
use App\Repository\ModeloRepository;
use App\Repository\VeiculoRepository;
use App\Helpers\SlugGenerator;

/**
 * FormRequest para validação dos campos comuns do veículo (tabela veiculos).
 *
 * Valida os campos principais, dimensões, status e flags.
 * Campos específicos (combustão, elétrico, híbrido, GNV)
 * são validados em requests separados.
 * 
 * A validação de opcionais é responsabilidade do VeiculoOpcionalRequest.
 */
class VeiculoRequest extends FormRequest
{
    private MarcaRepository $marcaRepo;
    private ModeloRepository $modeloRepo;
    private VeiculoRepository $veiculoRepo;
    private ?int $routeId = null;

    public function __construct(
        \App\Core\Request $request,
        MarcaRepository $marcaRepo,
        ModeloRepository $modeloRepo,
        VeiculoRepository $veiculoRepo
    ) {
        parent::__construct($request);
        $this->marcaRepo = $marcaRepo;
        $this->modeloRepo = $modeloRepo;
        $this->veiculoRepo = $veiculoRepo;
    }

    /**
     * Define o ID da rota (usado na edição para ignorar o próprio registro).
     *
     * @param int $id
     * @return void
     */
    public function setRouteId(int $id): void
    {
        $this->routeId = $id;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            // Campos obrigatórios (IDs de marca e modelo)
            'marca_id'       => 'required|integer|exists:marcas,id',
            'modelo_id'      => 'required|integer|exists:modelos,id',
            'ano_fabricacao' => 'required|integer|min_num:1900',
            'ano_modelo'     => 'required|integer|min_num:1900',
            'cor'            => 'required|max:30',
            'quilometragem'  => 'required|integer|min_num:0',

            // Tipo de veículo (obrigatório para decidir o complemento)
            'tipo_veiculo'   => 'required|in:combustao,eletrico,hibrido',

            // Opcionais (comuns)
            'versao'         => 'nullable|max:50',
            'numero_portas'  => 'required|integer|between:2,6',
            'numero_assentos'=> 'required|integer|between:2,15',

            // Dimensões (opcionais)
            'comprimento_mm'           => 'nullable|integer|min_num:0',
            'largura_mm'               => 'nullable|integer|min_num:0',
            'altura_mm'                => 'nullable|integer|min_num:0',
            'distancia_entre_eixos_mm' => 'nullable|integer|min_num:0',
            'peso_ordem_marcha_kg'     => 'nullable|numeric|min_num:0',
            'volume_porta_malas_l'     => 'nullable|integer|min_num:0',
            'volume_cacamba_l'         => 'nullable|integer|min_num:0',
            'carga_util_kg'            => 'nullable|numeric|min_num:0',
            'capacidade_reboque_kg'    => 'nullable|numeric|min_num:0',

            // Flags e status
            'gnv_instalado'   => 'nullable|boolean',
            'status_estoque'  => 'nullable|in:disponivel,vendido,reservado',
            'status_vitrine'  => 'nullable|in:ativo,inativo',

            // Novos campos opcionais
            'carroceria'           => 'nullable|string|max:30',
            'tipo_direcao'         => 'nullable|in:mecanica,hidraulica,eletrica,eletro-hidraulica',
            'altura_solo_mm'       => 'nullable|integer|min:0',
            'pneu_aro'             => 'nullable|integer|min_num:10|max_num:30',
            'tipo_roda'            => 'nullable|in:liga_leve,calota',
            'freio_dianteiro'      => 'nullable|in:disco,tambor',
            'freio_traseiro'       => 'nullable|in:disco,tambor',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            // Marca (ID)
            'marca_id.required' => 'A marca é obrigatória.',
            'marca_id.integer'  => 'A marca deve ser um número inteiro.',
            'marca_id.exists'   => 'A marca selecionada não existe.',

            // Modelo (ID)
            'modelo_id.required' => 'O modelo é obrigatório.',
            'modelo_id.integer'  => 'O modelo deve ser um número inteiro.',
            'modelo_id.exists'   => 'O modelo selecionado não existe.',

            // Ano
            'ano_fabricacao.required' => 'O ano de fabricação é obrigatório.',
            'ano_fabricacao.integer'  => 'O ano de fabricação deve ser um número inteiro.',
            'ano_fabricacao.min_num'  => 'O ano de fabricação deve ser maior que 1900.',
            'ano_modelo.required'     => 'O ano do modelo é obrigatório.',
            'ano_modelo.integer'      => 'O ano do modelo deve ser um número inteiro.',
            'ano_modelo.min_num'      => 'O ano do modelo deve ser maior que 1900.',

            // Cor
            'cor.required' => 'A cor é obrigatória.',
            'cor.max'      => 'A cor deve ter no máximo :max caracteres.',

            // Quilometragem
            'quilometragem.required' => 'A quilometragem é obrigatória.',
            'quilometragem.integer'  => 'A quilometragem deve ser um número inteiro.',
            'quilometragem.min_num'  => 'A quilometragem não pode ser negativa.',

            // Tipo de veículo
            'tipo_veiculo.required' => 'O tipo de veículo é obrigatório.',
            'tipo_veiculo.in'       => 'O tipo de veículo deve ser combustão, elétrico ou híbrido.',

            // Opcionais
            'versao.max'              => 'A versão deve ter no máximo :max caracteres.',
            'numero_portas.integer'   => 'O número de portas deve ser um número inteiro.',
            'numero_portas.between'   => 'O número de portas deve estar entre :min e :max.',
            'numero_assentos.integer' => 'O número de assentos deve ser um número inteiro.',
            'numero_assentos.between' => 'O número de assentos deve estar entre :min e :max.',

            // Dimensões
            'comprimento_mm.integer'            => 'O comprimento deve ser um número inteiro.',
            'comprimento_mm.min_num'            => 'O comprimento não pode ser negativo.',
            'largura_mm.integer'                => 'A largura deve ser um número inteiro.',
            'largura_mm.min_num'                => 'A largura não pode ser negativa.',
            'altura_mm.integer'                 => 'A altura deve ser um número inteiro.',
            'altura_mm.min_num'                 => 'A altura não pode ser negativa.',
            'distancia_entre_eixos_mm.integer'  => 'A distância entre eixos deve ser um número inteiro.',
            'distancia_entre_eixos_mm.min_num'  => 'A distância entre eixos não pode ser negativa.',
            'peso_ordem_marcha_kg.numeric'      => 'O peso deve ser um número válido.',
            'peso_ordem_marcha_kg.min_num'      => 'O peso não pode ser negativo.',
            'volume_porta_malas_l.integer'      => 'O volume do porta-malas deve ser um número inteiro.',
            'volume_porta_malas_l.min_num'      => 'O volume do porta-malas não pode ser negativo.',
            'volume_cacamba_l.integer'          => 'O volume da caçamba deve ser um número inteiro.',
            'volume_cacamba_l.min_num'          => 'O volume da caçamba não pode ser negativo.',
            'carga_util_kg.numeric'             => 'A carga útil deve ser um número válido.',
            'carga_util_kg.min_num'             => 'A carga útil não pode ser negativa.',
            'capacidade_reboque_kg.numeric'     => 'A capacidade de reboque deve ser um número válido.',
            'capacidade_reboque_kg.min_num'     => 'A capacidade de reboque não pode ser negativa.',

            // Flags
            'gnv_instalado.boolean' => 'O campo GNV instalado deve ser verdadeiro ou falso.',

            // Status
            'status_estoque.in' => 'O status de estoque deve ser disponível, vendido ou reservado.',
            'status_vitrine.in' => 'O status da vitrine deve ser ativo ou inativo.',

            // Carroceria
            'carroceria.string' => 'O tipo de carroceria deve ser um texto.',
            'carroceria.max'    => 'O tipo de carroceria deve ter no máximo :max caracteres.',

            // Direção
            'tipo_direcao.in'   => 'O tipo de direção deve ser: mecânica, hidráulica, elétrica ou eletro-hidráulica.',

            // Altura do solo
            'altura_solo_mm.integer' => 'A altura do solo deve ser um número inteiro.',
            'altura_solo_mm.min'     => 'A altura do solo não pode ser negativa.',

            // Aro
            'pneu_aro.integer' => 'O aro do pneu deve ser um número inteiro.',
            'pneu_aro.min'     => 'O aro do pneu deve ser no mínimo 10 polegadas.',
            'pneu_aro.max'     => 'O aro do pneu deve ser no máximo 30 polegadas.',

            // Tipo de roda
            'tipo_roda.in'     => 'O tipo de roda deve ser liga leve ou calota.',

            // Freios
            'freio_dianteiro.in' => 'O tipo de freio dianteiro deve ser disco ou tambor.',
            'freio_traseiro.in'  => 'O tipo de freio traseiro deve ser disco ou tambor.',
        ];
    }

    /**
     * Sobrescreve a sanitização para aplicar regras específicas.
     *
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data): array
    {
        $data = parent::sanitize($data);

        // 1. Converter strings vazias para NULL em TODOS os campos (independente de obrigatoriedade)
        foreach ($data as $key => $value) {
            if (is_string($value) && $value === '') {
                $data[$key] = null;
            }
        }

        // 2. Listas de campos por tipo
        $intFields = [
            'marca_id', 'modelo_id',
            'ano_fabricacao', 'ano_modelo',
            'quilometragem',
            'numero_portas', 'numero_assentos',
            'pneu_aro', 'pneu_aro_outro',
            'comprimento_mm', 'largura_mm', 'altura_mm',
            'altura_solo_mm',
            'distancia_entre_eixos_mm',
            'volume_porta_malas_l', 'volume_cacamba_l'
        ];

        $decimalFields = [
            'peso_ordem_marcha_kg',
            'carga_util_kg',
            'capacidade_reboque_kg'
        ];

        $stringFields = [
            'carroceria', 'carroceria_outro',
            'versao',
            'cor', 'cor_outro',
            'tipo_direcao',
            'freio_dianteiro', 'freio_traseiro',
            'tipo_roda',
            'status_estoque', 'status_vitrine'
        ];

        $booleanFields = [
            'gnv_instalado'
        ];

        // 3. Converter inteiros
        foreach ($intFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        // 4. Converter decimais (com tratamento de vírgula/ponto)
        foreach ($decimalFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $value = trim($data[$field]);
                // Remove pontos de milhar (ex: 1.500 -> 1500)
                $value = str_replace('.', '', $value);
                // Converte vírgula para ponto (ex: 12,5 -> 12.5)
                $value = str_replace(',', '.', $value);
                if (is_numeric($value)) {
                    $data[$field] = (float) $value;
                }
            } elseif (isset($data[$field]) && is_numeric($data[$field])) {
                // Se já for numérico, converte para float
                $data[$field] = (float) $data[$field];
            }
        }

        // 5. Sanitizar strings (trim e null se vazio)
        foreach ($stringFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
                // Reforça a conversão para null (já feita no passo 1, mas mantido por segurança)
                if ($data[$field] === '') {
                    $data[$field] = null;
                }
            }
        }

        // 6. Converter booleanos
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) (bool) $data[$field];
            }
        }

        // 7. Normaliza status_estoque e status_vitrine (garantir valores válidos)
        if (isset($data['status_estoque']) && !in_array($data['status_estoque'], ['disponivel', 'vendido', 'reservado'], true)) {
            $data['status_estoque'] = 'disponivel';
        }

        if (isset($data['status_vitrine']) && !in_array($data['status_vitrine'], ['ativo', 'inativo'], true)) {
            $data['status_vitrine'] = 'inativo';
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     * 
     * Adiciona validação de unicidade do slug e geração automática,
     * além de validação condicional para GNV (apenas veículos a combustão).
     */
    public function validate(): bool
    {
        // 1. Executa a validação base
        if (!parent::validate()) {
            return false;
        }

        // 2. Obtém os dados validados
        $data = $this->validated();

        // 3. Valida condicional: GNV só permitido em veículos a combustão
        if (!empty($data['gnv_instalado']) && ($data['tipo_veiculo'] ?? '') !== 'combustao') {
            $this->addError('gnv_instalado', 'O kit GNV só pode ser instalado em veículos a combustão.');
            return false;
        }

        // 4. Extrai os IDs e o ano do modelo dos dados validados
        $marcaId = (int) ($data['marca_id'] ?? 0);
        $modeloId = (int) ($data['modelo_id'] ?? 0);
        $anoModelo = (int) ($data['ano_modelo'] ?? 0);

        // Verifica se os IDs são válidos (redundante, pois são obrigatórios, mas seguro)
        if ($marcaId === 0 || $modeloId === 0 || $anoModelo === 0) {
            // Isso não deve ocorrer devido à validação required, mas mantemos segurança
            return true;
        }

        // 5. Busca os nomes da marca e modelo
        $marca = $this->marcaRepo->findById($marcaId);
        $modelo = $this->modeloRepo->findById($modeloId);

        if (!$marca || !$modelo) {
            // Se não encontrar, não adicionamos erro aqui (já foi validado exists)
            return true;
        }

        // 6. Gera o slug
        $slug = SlugGenerator::generate($marca['nome'], $modelo['nome'], $anoModelo);

        // 7. Verifica unicidade do slug
        $exists = $this->veiculoRepo->findBySlug($slug);
        if ($exists) {
            // Se for edição, ignora se o slug pertence ao próprio veículo
            if ($this->routeId !== null && (int)$exists['id'] === $this->routeId) {
                // Pertence ao mesmo veículo, ok
            } else {
                // Conflito: adiciona erro
                $this->addError('slug', 'Já existe um veículo com esse slug. Por favor, altere o ano ou modelo.');
                return false;
            }
        }

        // 8. Armazena o slug nos dados validados para uso posterior
        $this->validated['slug'] = $slug;

        return true;
    }

    /**
     * Adiciona um erro ao campo (usado internamente).
     *
     * @param string $field
     * @param string $message
     * @return void
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Retorna os dados principais do veículo já validados e sanitizados.
     * Apenas os campos enviados na requisição são retornados.
     *
     * @return array
     */
    public function getDadosPrincipais(): array
    {
        return $this->validated();
    }

    /**
     * Retorna o tipo de veículo selecionado.
     *
     * @return string
     */
    public function getTipoVeiculo(): string
    {
        $validated = $this->validated();
        return $validated['tipo_veiculo'] ?? 'combustao';
    }

    /**
     * Verifica se o veículo possui GNV instalado.
     *
     * @return bool
     */
    public function hasGNV(): bool
    {
        $validated = $this->validated();
        return !empty($validated['gnv_instalado']);
    }
}

/**
 * ========================================================================
 * FORM REQUEST PARA VEÍCULOS (PRINCIPAIS)
 * ========================================================================
 * 
 * Este FormRequest gerencia a validação e sanitização dos campos
 * principais da tabela `veiculos`.
 * 
 * CARACTERÍSTICAS PRINCIPAIS:
 * 
 * 1. VALIDAÇÃO DECLARATIVA (rules())
 *    - Define regras por campo (obrigatoriedade, tipo, tamanho, valores)
 *    - Usa regras customizadas do sistema: `min_num`, `max_num`, `exists`, etc.
 *    - Separa campos obrigatórios (`required`) de opcionais (`nullable`)
 *    - Inclui validação de existência no banco (`exists:marcas,id`)
 * 
 * 2. MENSAGENS PERSONALIZADAS (messages())
 *    - Mensagens para cada regra de cada campo
 *    - Permite placeholders como `:min`, `:max`, `:values`
 *    - Deve cobrir todas as regras definidas em rules()
 * 
 * 3. SANITIZAÇÃO GENÉRICA (sanitize())
 *    - **Filosofia:** Tratar todos os campos igualmente, sem distinguir
 *      obrigatórios de opcionais. A validação `required` é feita depois.
 *    - **Passo 1:** Converter TODAS as strings vazias para `NULL`.
 *      Isso unifica valores vazios para todos os campos.
 *    - **Passo 2:** Aplicar conversão de tipo conforme listas:
 *        - `$intFields`    → (int) para campos numéricos inteiros
 *        - `$decimalFields` → (float) com tratamento de vírgula/ponto
 *        - `$stringFields`  → trim() e null se vazio
 *        - `$booleanFields` → (int) (bool) para 0/1
 *    - **Importante:** A sanitização NÃO deve saber se o campo é obrigatório.
 *      A validação `required` irá falhar se o campo vier `NULL` ou vazio.
 * 
 * 4. VALIDAÇÃO CUSTOMIZADA (validate())
 *    - Executada APÓS a validação base (parent::validate())
 *    - Adiciona regras de negócio que não são cobertas pelas regras simples:
 *        - Validação condicional (ex: GNV só em veículos a combustão)
 *        - Geração automática de slug com base em marca, modelo e ano
 *        - Verificação de unicidade do slug (com tratamento para edição)
 *    - Pode adicionar erros customizados via `addError()`
 * 
 * 5. MÉTODOS AUXILIARES
 *    - `getDadosPrincipais()` → retorna apenas os dados validados
 *    - `getTipoVeiculo()` → retorna o tipo selecionado
 *    - `hasGNV()` → verifica se o veículo possui GNV
 *    - `setRouteId()` → define o ID da rota para ignorar próprio registro na edição
 * 
 * ========================================================================
 * COMO ADAPTAR PARA OUTROS FORM REQUESTS:
 * 
 * 1. Defina as regras em `rules()`
 * 2. Defina as mensagens em `messages()`
 * 3. No `sanitize()`:
 *    - Mantenha a conversão genérica de strings vazias para NULL
 *    - Crie listas de campos por tipo (int, decimal, string, boolean)
 *    - Aplique as conversões apropriadas
 *    - Adicione normalizações específicas se necessário
 * 4. No `validate()`:
 *    - Adicione regras de negócio específicas
 *    - Use `addError()` para erros customizados
 *    - Não se preocupe com obrigatoriedade (já tratada em rules)
 * 
 * ========================================================================
 * EXEMPLO DE FLUXO:
 * 
 * 1. Dados chegam → `sanitize()` limpa e converte tipos
 * 2. `validate()` executa regras base (parent::validate())
 * 3. `validate()` executa regras customizadas
 * 4. Dados validados ficam disponíveis em `validated()`
 * 5. Controller chama `getDadosPrincipais()` para obter dados limpos
 * 
 * ========================================================================
 */ 