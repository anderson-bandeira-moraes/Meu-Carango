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
            'quilometragem'  => 'required|numeric|min_num:0',

            // Tipo de veículo (obrigatório para decidir o complemento)
            'tipo_veiculo'   => 'required|in:combustao,eletrico,hibrido',

            // Opcionais (comuns)
            'versao'         => 'nullable|max:50',
            'numero_portas'  => 'nullable|integer|between:2,6',
            'numero_assentos'=> 'nullable|integer|between:2,15',

            // Dimensões (opcionais)
            'comprimento_mm'           => 'nullable|numeric|min_num:0',
            'largura_mm'               => 'nullable|numeric|min_num:0',
            'altura_mm'                => 'nullable|numeric|min_num:0',
            'distancia_entre_eixos_mm' => 'nullable|numeric|min_num:0',
            'peso_ordem_marcha_kg'     => 'nullable|numeric|min_num:0',
            'volume_porta_malas_l'     => 'nullable|numeric|min_num:0',
            'volume_cacamba_l'         => 'nullable|numeric|min_num:0',
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
            'pneu_aro'             => 'nullable|integer|min:10|max:30',
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
            'quilometragem.numeric'  => 'A quilometragem deve ser um número válido.',
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
            'comprimento_mm.numeric' => 'O comprimento deve ser um número válido.',
            'comprimento_mm.min_num' => 'O comprimento não pode ser negativo.',
            'largura_mm.numeric'     => 'A largura deve ser um número válido.',
            'largura_mm.min_num'     => 'A largura não pode ser negativa.',
            'altura_mm.numeric'      => 'A altura deve ser um número válido.',
            'altura_mm.min_num'      => 'A altura não pode ser negativa.',
            'distancia_entre_eixos_mm.numeric' => 'A distância entre eixos deve ser um número válido.',
            'distancia_entre_eixos_mm.min_num' => 'A distância entre eixos não pode ser negativa.',
            'peso_ordem_marcha_kg.numeric'     => 'O peso deve ser um número válido.',
            'peso_ordem_marcha_kg.min_num'     => 'O peso não pode ser negativo.',
            'volume_porta_malas_l.numeric'     => 'O volume do porta-malas deve ser um número válido.',
            'volume_porta_malas_l.min_num'     => 'O volume do porta-malas não pode ser negativo.',
            'volume_cacamba_l.numeric'         => 'O volume da caçamba deve ser um número válido.',
            'volume_cacamba_l.min_num'         => 'O volume da caçamba não pode ser negativo.',
            'carga_util_kg.numeric'            => 'A carga útil deve ser um número válido.',
            'carga_util_kg.min_num'            => 'A carga útil não pode ser negativa.',
            'capacidade_reboque_kg.numeric'    => 'A capacidade de reboque deve ser um número válido.',
            'capacidade_reboque_kg.min_num'    => 'A capacidade de reboque não pode ser negativa.',

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

        // Converte vírgula decimal para ponto nos campos numéricos
        $decimalFields = [
            'quilometragem',
            'comprimento_mm', 'largura_mm', 'altura_mm',
            'distancia_entre_eixos_mm', 'peso_ordem_marcha_kg',
            'volume_porta_malas_l', 'volume_cacamba_l',
            'carga_util_kg', 'capacidade_reboque_kg'
        ];

        foreach ($decimalFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                // Remove espaços
                $value = trim($data[$field]);
                // Remove pontos de milhar (ex: 1.500 -> 1500)
                $value = str_replace('.', '', $value);
                // Converte vírgula para ponto (ex: 12,5 -> 12.5)
                $value = str_replace(',', '.', $value);
                // Se for numérico, converte para float
                if (is_numeric($value)) {
                    $data[$field] = (float) $value;
                }
            }
        }

        // Normaliza gnv_instalado para 0 ou 1
        if (isset($data['gnv_instalado'])) {
            $data['gnv_instalado'] = (int) (bool) $data['gnv_instalado'];
        }

        // Normaliza status_estoque e status_vitrine (garantir valores válidos)
        if (isset($data['status_estoque']) && !in_array($data['status_estoque'], ['disponivel', 'vendido', 'reservado'], true)) {
            $data['status_estoque'] = 'disponivel';
        }

        if (isset($data['status_vitrine']) && !in_array($data['status_vitrine'], ['ativo', 'inativo'], true)) {
            $data['status_vitrine'] = 'inativo';
        }

        // Converte strings vazias para null em campos numéricos opcionais
        $numericOptionalFields = [
            'comprimento_mm', 'largura_mm', 'altura_mm',
            'distancia_entre_eixos_mm', 'peso_ordem_marcha_kg',
            'volume_porta_malas_l', 'volume_cacamba_l',
            'carga_util_kg', 'capacidade_reboque_kg',
            'numero_portas', 'numero_assentos',
            'altura_solo_mm', 'pneu_aro'
        ];

        foreach ($numericOptionalFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        // Converte strings vazias para null em campos de texto/enum opcionais
        $stringOptionalFields = [
            'carroceria', 'tipo_direcao', 'tipo_roda',
            'freio_dianteiro', 'freio_traseiro'
        ];

        foreach ($stringOptionalFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
                if ($data[$field] === '') {
                    $data[$field] = null;
                }
            }
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

        // 4. Verifica se marca_id e modelo_id estão presentes (já validados)
        $marcaId = $data['marca_id'] ?? null;
        $modeloId = $data['modelo_id'] ?? null;
        $anoModelo = (int) ($data['ano_modelo'] ?? 0);

        // Converte para inteiro antes de usar
        $marcaId = (int) $marcaId;
        $modeloId = (int) $modeloId;

        if (!$marcaId || !$modeloId || !$anoModelo) {
            // Isso não deve ocorrer porque são obrigatórios, mas mantemos segurança
            return true; // não geramos erro aqui, pois os campos já foram validados
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