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
            'preco'          => 'required|numeric|min_num:0',

            // Tipo de veículo (obrigatório para decidir o complemento)
            'tipo_veiculo'   => 'required|in:combustao,eletrico,hibrido',

            // Opcionais (comuns)
            'versao'         => 'nullable|max:50',
            'numero_portas'  => 'nullable|integer|between:2,6',
            'numero_assentos'=> 'nullable|integer|between:2,15',

            // Dimensões (opcionais)
            'comprimento_mm' => 'nullable|integer|min_num:0',
            'largura_mm'     => 'nullable|integer|min_num:0',
            'altura_mm'      => 'nullable|integer|min_num:0',
            'distancia_entre_eixos_mm' => 'nullable|integer|min_num:0',
            'peso_ordem_marcha_kg'     => 'nullable|integer|min_num:0',
            'volume_porta_malas_l'     => 'nullable|integer|min_num:0',
            'volume_cacamba_l'         => 'nullable|integer|min_num:0',
            'carga_util_kg'            => 'nullable|integer|min_num:0',
            'capacidade_reboque_kg'    => 'nullable|integer|min_num:0',

            // Flags e status
            'gnv_instalado'   => 'nullable|boolean',
            'status_estoque'  => 'nullable|in:disponivel,vendido,reservado',
            'status_vitrine'  => 'nullable|in:ativo,inativo',
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

            // Preço
            'preco.required' => 'O preço é obrigatório.',
            'preco.numeric'  => 'O preço deve ser um número válido.',
            'preco.min_num'  => 'O preço não pode ser negativo.',

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
            'comprimento_mm.integer' => 'O comprimento deve ser um número inteiro.',
            'comprimento_mm.min_num' => 'O comprimento não pode ser negativo.',
            'largura_mm.integer'     => 'A largura deve ser um número inteiro.',
            'largura_mm.min_num'     => 'A largura não pode ser negativa.',
            'altura_mm.integer'      => 'A altura deve ser um número inteiro.',
            'altura_mm.min_num'      => 'A altura não pode ser negativa.',
            'distancia_entre_eixos_mm.integer' => 'A distância entre eixos deve ser um número inteiro.',
            'distancia_entre_eixos_mm.min_num' => 'A distância entre eixos não pode ser negativa.',
            'peso_ordem_marcha_kg.integer'     => 'O peso deve ser um número inteiro.',
            'peso_ordem_marcha_kg.min_num'     => 'O peso não pode ser negativo.',
            'volume_porta_malas_l.integer'     => 'O volume do porta-malas deve ser um número inteiro.',
            'volume_porta_malas_l.min_num'     => 'O volume do porta-malas não pode ser negativo.',
            'volume_cacamba_l.integer'         => 'O volume da caçamba deve ser um número inteiro.',
            'volume_cacamba_l.min_num'         => 'O volume da caçamba não pode ser negativo.',
            'carga_util_kg.integer'            => 'A carga útil deve ser um número inteiro.',
            'carga_util_kg.min_num'            => 'A carga útil não pode ser negativa.',
            'capacidade_reboque_kg.integer'    => 'A capacidade de reboque deve ser um número inteiro.',
            'capacidade_reboque_kg.min_num'    => 'A capacidade de reboque não pode ser negativa.',

            // Flags
            'gnv_instalado.boolean' => 'O campo GNV instalado deve ser verdadeiro ou falso.',

            // Status
            'status_estoque.in' => 'O status de estoque deve ser disponível, vendido ou reservado.',
            'status_vitrine.in' => 'O status da vitrine deve ser ativo ou inativo.',
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

        // Normaliza preco: remove pontos de milhar e converte vírgula decimal para ponto
        if (isset($data['preco']) && is_string($data['preco'])) {
            $data['preco'] = str_replace('.', '', $data['preco']); // remove pontos de milhar
            $data['preco'] = str_replace(',', '.', $data['preco']); // vírgula -> ponto
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
     * Retorna os dados da tabela veiculos já validados e sanitizados.
     *
     * @return array
     */
    public function getDadosPrincipais(): array
    {
        $validated = $this->validated();

        return [
            'marca_id'              => $validated['marca_id'] ?? null,
            'modelo_id'             => $validated['modelo_id'] ?? null,
            'versao'                => $validated['versao'] ?? null,
            'ano_fabricacao'        => $validated['ano_fabricacao'] ?? null,
            'ano_modelo'            => $validated['ano_modelo'] ?? null,
            'cor'                   => $validated['cor'] ?? '',
            'quilometragem'         => $validated['quilometragem'] ?? 0,
            'preco'                 => (float) ($validated['preco'] ?? 0),
            'numero_portas'         => $validated['numero_portas'] ?? null,
            'numero_assentos'       => $validated['numero_assentos'] ?? null,
            'comprimento_mm'        => $validated['comprimento_mm'] ?? null,
            'largura_mm'            => $validated['largura_mm'] ?? null,
            'altura_mm'             => $validated['altura_mm'] ?? null,
            'distancia_entre_eixos_mm' => $validated['distancia_entre_eixos_mm'] ?? null,
            'peso_ordem_marcha_kg'  => $validated['peso_ordem_marcha_kg'] ?? null,
            'volume_porta_malas_l'  => $validated['volume_porta_malas_l'] ?? null,
            'volume_cacamba_l'      => $validated['volume_cacamba_l'] ?? null,
            'carga_util_kg'         => $validated['carga_util_kg'] ?? null,
            'capacidade_reboque_kg' => $validated['capacidade_reboque_kg'] ?? null,
            'gnv_instalado'         => $validated['gnv_instalado'] ?? 0,
            'status_estoque'        => $validated['status_estoque'] ?? 'disponivel',
            'status_vitrine'        => $validated['status_vitrine'] ?? 'inativo',
            'slug'                  => $validated['slug'] ?? null, // Inclui o slug gerado
        ];
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