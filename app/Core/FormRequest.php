<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use RuntimeException;

/**
 * Classe base para validação de requisições (FormRequest).
 * 
 * Estende a Request para herdar todos os métodos de acesso aos dados HTTP,
 * e adiciona funcionalidades de validação, sanitização e mensagens personalizadas.
 * 
 * @package App\Core
 */
abstract class FormRequest extends Request
{
    /**
     * @var array<string, array<string>> Erros de validação no formato campo => [mensagens]
     */
    protected array $errors = [];

    /**
     * @var array<string, mixed> Dados que passaram na validação (campo => valor)
     */
    protected array $validated = [];

    /**
     * @var array<string, string> Regras de validação com parâmetros em cache
     */
    private array $parsedRules = [];

    /**
     * Serviço de validação de banco (injetado externamente).
     */
    private ?Validator $validator = null;

    /**
     * Construtor – recebe a Request atual e repassa os dados para a classe pai.
     *
     * @param Request $request A requisição HTTP atual
     */
    public function __construct(Request $request)
    {
        parent::__construct(
            $request->getQuery(),
            $request->getPost(),
            $request->getFile(),
            $request->getServer(),
            $request->getCookies()
        );
    }

    /**
     * Injeta o serviço de validação de banco.
     * Chamado pelo container em index.php.
     */
    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * Retorna o Validator injetado ou falha com erro de wiring.
     *
     * @throws RuntimeException Se o Validator não foi injetado.
     */
    private function validator(): Validator
    {
        if ($this->validator === null) {
            throw new RuntimeException(sprintf(
                'Validator não injetado em %s. Verifique o registro no container em index.php.',
                static::class
            ));
        }

        return $this->validator;
    }

    /**
     * Define as regras de validação para os campos da requisição.
     *
     * Exemplo:
     * [
     *     'email' => 'required|email|max:100',
     *     'senha' => 'required|min:6',
     *     'telefone' => 'max:20', // opcional
     * ]
     *
     * @return array<string, string> Mapa campo => regras (separadas por '|')
     */
    abstract public function rules(): array;

    /**
     * Define mensagens personalizadas para regras específicas.
     *
     * Exemplo:
     * [
     *     'email.required' => 'O e-mail é obrigatório',
     *     'email.email'    => 'Informe um e-mail válido',
     *     'senha.min'      => 'A senha deve ter no mínimo :min caracteres',
     * ]
     *
     * @return array<string, string> Mapa campo.regra => mensagem
     */
    abstract public function messages(): array;

    /**
     * Executa a validação de todos os campos com base nas regras definidas.
     *
     * @return bool True se a validação passou, false caso contrário
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->validated = [];

        // 1. Obtém e sanitiza todos os dados da requisição
        $data = $this->sanitize($this->all());
        $rules = $this->rules();

        // 2. Percorre as regras
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $isRequired = $this->isRequired($ruleString);

            // Verifica se o campo foi enviado E tem valor não vazio (após sanitização)
            $isPresent = $this->fieldExists($field, $data) && $value !== '' && $value !== null;

            // Se não foi enviado (ou está vazio) e não é obrigatório, ignora
            if (!$isPresent && !$isRequired) {
                continue;
            }

            // Se é obrigatório e não foi enviado (ou está vazio), adiciona erro
            if ($isRequired && !$isPresent) {
                $this->addError($field, 'required');
                continue;
            }

            // Aplica as regras ao valor (se chegou aqui, o campo está presente e não vazio)
            if (!$this->applyRules($field, $value, $ruleString)) {
                // Os erros já foram adicionados dentro de applyRules
            }
        }

        // 3. Guarda os campos que passaram (incluindo null)
        $validated = [];
        foreach ($rules as $field => $ruleString) {
            if (array_key_exists($field, $data) && !isset($this->errors[$field])) {
                $value = $data[$field];
                if ($value !== '') {
                    $validated[$field] = $value;
                }
            }
        }
        $this->validated = $validated;

        return empty($this->errors);
    }

    /**
     * Retorna os dados que passaram na validação (campo => valor).
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /**
     * Retorna os erros de validação.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Verifica se a validação falhou.
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Verifica se a validação passou.
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Verifica se o campo existe no array de dados (considerando null como "não enviado").
     *
     * @param string $field
     * @param array $data
     * @return bool
     */
    private function fieldExists(string $field, array $data): bool
    {
        return array_key_exists($field, $data);
    }

    /**
     * Verifica se a string de regras contém a regra 'required'.
     *
     * @param string $ruleString
     * @return bool
     */
    private function isRequired(string $ruleString): bool
    {
        $rules = explode('|', $ruleString);
        return in_array('required', $rules, true);
    }

    /**
     * Aplica a sanitização básica a todos os dados.
     * - trim() em strings
     *
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
            // null permanece null (não converte para '')
        }
        return $data;
    }

    /**
     * Aplica uma string de regras a um campo e valor.
     * Adiciona erros ao array $errors se alguma regra falhar.
     *
     * @param string $field
     * @param mixed $value
     * @param string $ruleString
     * @return bool True se todas as regras passarem
     */
    private function applyRules(string $field, mixed $value, string $ruleString): bool
    {
        $rules = explode('|', $ruleString);
        $passed = true;

        foreach ($rules as $rule) {
            // Regra vazia? ignora
            if (trim($rule) === '') {
                continue;
            }

            [$ruleName, $ruleParams] = $this->parseRule($rule);

            // Pula required (já tratado antes) e nullable (não precisa validar)
            if ($ruleName === 'required' || $ruleName === 'nullable') {
                continue;
            }

            // Aplica a regra
            $result = $this->applySingleRule($field, $value, $ruleName, $ruleParams);

            if (!$result) {
                $this->addError($field, $ruleName, $ruleParams);
                $passed = false;
                // Não paramos para coletar todos os erros (opcional: podemos parar no primeiro erro)
            }
        }

        return $passed;
    }

    /**
     * Aplica uma única regra a um valor.
     *
     * @param string $field (usado para mensagens)
     * @param mixed $value
     * @param string $ruleName
     * @param array $params
     * @return bool
     * Regras suportadas: required, email, min, max, min_num, max_num,
     * numeric, integer, regex, url, boolean, array, nullable,
     * in, between, date, string, exists
     */
    private function applySingleRule(string $field, mixed $value, string $ruleName, array $params): bool
    {
        switch ($ruleName) {
            case 'required':
                return $value !== null && $value !== '';
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'min':
                return $this->validateMin($value, $params[0] ?? 0);
            case 'max':
                return $this->validateMax($value, $params[0] ?? PHP_INT_MAX);
            case 'min_num':
                return $this->validateNumericMin($value, $params[0] ?? 0);
            case 'max_num':
                return $this->validateNumericMax($value, $params[0] ?? PHP_INT_MAX);
            case 'numeric':
                return is_numeric($value);
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'regex':
                return $this->validateRegex($value, $params[0] ?? '');
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'boolean':
                return in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true);
            case 'array':
                return is_array($value);
            case 'nullable':
                return true; // já tratado, mas se chegar aqui, passa
            case 'in':
                return $this->validateIn($value, $params);
            case 'between':
                return $this->validateBetween($value, $params);
            case 'date':
                return $this->validateDate($value);
            case 'string':
                return $this->validateString($value);
            case 'exists':
                return $this->validateExists($value, $params);
            default:
                $this->handleUnknownRule($ruleName);
                return true;
        }
    }

    /**
     * Trata uma regra desconhecida.
     * Em desenvolvimento, lança exceção. Em produção, loga e passa.
     *
     * @throws RuntimeException Em ambiente de desenvolvimento.
     */
    private function handleUnknownRule(string $ruleName): void
    {
        $env = $_ENV['APP_ENV'] ?? 'production';

        if ($env === 'development') {
            throw new RuntimeException("Regra desconhecida no FormRequest: {$ruleName}");
        }

        error_log("Regra desconhecida no FormRequest: {$ruleName}");
    }

    /**
     * Regra in: o valor deve estar na lista de parâmetros.
     */
    private function validateIn(mixed $value, array $params): bool
    {
        if (empty($params)) {
            throw new InvalidArgumentException('A regra in requer pelo menos 1 parâmetro.');
        }

        return in_array($value, $params, true);
    }

    /**
     * Regra between: o valor deve estar entre min e max (inclusive).
     */
    private function validateBetween(mixed $value, array $params): bool
    {
        if (count($params) < 2) {
            throw new InvalidArgumentException('A regra between requer 2 parâmetros: between:min,max.');
        }

        if (!is_numeric($value)) {
            return false;
        }

        $min = (float) $params[0];
        $max = (float) $params[1];
        $val = (float) $value;

        return $val >= $min && $val <= $max;
    }

    /**
     * Regra date: aceita datas no formato YYYY-MM-DD e valida com checkdate.
     */
    private function validateDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [$y, $m, $d] = explode('-', $value);

        return checkdate((int) $m, (int) $d, (int) $y);
    }

    /**
     * Regra string: verifica se o valor é string.
     */
    private function validateString(mixed $value): bool
    {
        return is_string($value);
    }

    /**
     * Regra exists: verifica se o valor existe na tabela/coluna.
     */
    private function validateExists(mixed $value, array $params): bool
    {
        if (count($params) < 2) {
            throw new InvalidArgumentException('A regra exists requer 2 parâmetros: exists:tabela,coluna.');
        }

        [$table, $column] = $params;

        return $this->validator()->exists($table, $column, $value);
    }

    /**
     * Valida regra min: verifica se o comprimento da string é maior ou igual ao mínimo.
     * 
     * Nota: Esta regra sempre conta caracteres, independentemente de o valor ser numérico.
     * Para validação de valor numérico, use a regra 'numeric' em combinação com 'min'.
     *
     * @param mixed $value O valor a ser validado (será convertido para string)
     * @param mixed $min O comprimento mínimo (será convertido para int)
     * @return bool True se o comprimento da string for >= $min, false caso contrário
     */
    private function validateMin(mixed $value, mixed $min): bool
    {
        $min = (int) $min;
        return mb_strlen((string) $value) >= $min;
    }

    /**
     * Valida regra max: verifica se o comprimento da string é menor ou igual ao máximo.
     * 
     * Nota: Esta regra sempre conta caracteres, independentemente de o valor ser numérico.
     * Para validação de valor numérico, use a regra 'numeric' em combinação com 'max'.
     *
     * @param mixed $value O valor a ser validado (será convertido para string)
     * @param mixed $max O comprimento máximo (será convertido para int)
     * @return bool True se o comprimento da string for <= $max, false caso contrário
     */
    private function validateMax(mixed $value, mixed $max): bool
    {
        $max = (int) $max;
        return mb_strlen((string) $value) <= $max;
    }

    /**
     * Valida se o valor numérico é maior ou igual ao mínimo.
     */
    private function validateNumericMin(mixed $value, mixed $min): bool
    {
        $min = (float) $min;
        return is_numeric($value) && (float) $value >= $min;
    }

    /**
     * Valida se o valor numérico é menor ou igual ao máximo.
     */
    private function validateNumericMax(mixed $value, mixed $max): bool
    {
        $max = (float) $max;
        return is_numeric($value) && (float) $value <= $max;
    }

    /**
     * Valida regra regex.
     *
     * @param mixed $value
     * @param string $pattern
     * @return bool
     */
    private function validateRegex(mixed $value, string $pattern): bool
    {
        if ($pattern === '') {
            return true;
        }
        return preg_match($pattern, (string) $value) === 1;
    }

    /**
     * Faz o parsing de uma regra com parâmetros.
     * Exemplo: 'min:6' → ['min', ['6']]
     * Exemplo: 'regex:/^[a-z]+$/' → ['regex', ['/^[a-z]+$/']]
     *
     * @param string $rule
     * @return array{0: string, 1: array<string>}
     */
    private function parseRule(string $rule): array
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];
        return [$ruleName, $params];
    }

    /**
     * Adiciona um erro para um campo e regra específica.
     *
     * @param string $field
     * @param string $rule
     * @param array $params (opcional) para substituir placeholders na mensagem
     */
    private function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->getMessage($field, $rule);
        $message = $this->replacePlaceholders($message, $params);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Obtém a mensagem personalizada para um campo e regra, ou mensagem padrão.
     *
     * @param string $field
     * @param string $rule
     * @return string
     */
    private function getMessage(string $field, string $rule): string
    {
        $messages = $this->messages();
        $key = $field . '.' . $rule;

        if (isset($messages[$key])) {
            return $messages[$key];
        }

        // Mensagens padrão (fallback)
        return match ($rule) {
            'required' => "O campo {$field} é obrigatório.",
            'email'    => "O campo {$field} deve ser um e-mail válido.",
            'min'      => "O campo {$field} deve ter no mínimo :min caracteres.",
            'max'      => "O campo {$field} deve ter no máximo :max caracteres.",
            'min_num'  => "O campo {$field} deve ser maior ou igual a :min.",
            'max_num'  => "O campo {$field} deve ser menor ou igual a :max.",
            'numeric'  => "O campo {$field} deve ser um número.",
            'integer'  => "O campo {$field} deve ser um número inteiro.",
            'regex'    => "O campo {$field} não tem um formato válido.",
            'url'      => "O campo {$field} deve ser uma URL válida.",
            'boolean'  => "O campo {$field} deve ser verdadeiro ou falso.",
            'array'    => "O campo {$field} deve ser um array.",
            'in'       => "O campo {$field} deve ser um dos valores permitidos: :values.",
            'between'  => "O campo {$field} deve estar entre :min e :max.",
            'date'     => "O campo {$field} deve ser uma data válida no formato YYYY-MM-DD.",
            'string'   => "O campo {$field} deve ser um texto.",
            'exists'   => "O valor informado em {$field} não existe.",
            default    => "O campo {$field} é inválido.",
        };
    }

    /**
     * Substitui placeholders (:min, :max, etc.) na mensagem com os valores dos parâmetros.
     *
     * @param string $message
     * @param array $params
     * @return string
     */
    private function replacePlaceholders(string $message, array $params): string
    {
        // :values → lista completa (usado por `in`)
        if (str_contains($message, ':values')) {
            $message = str_replace(':values', implode(', ', $params), $message);
        }

        // :min → primeiro parâmetro (usado por min, min_num, between)
        if (isset($params[0]) && str_contains($message, ':min')) {
            $message = str_replace(':min', (string) $params[0], $message);
        }

        // :max → segundo parâmetro se houver (between), senão primeiro (max, max_num)
        if (str_contains($message, ':max')) {
            $maxValue = $params[1] ?? $params[0] ?? '';
            $message = str_replace(':max', (string) $maxValue, $message);
        }

        return $message;
    }

    /**
     * Verifica se a requisição espera uma resposta JSON.
     * Útil para controllers decidirem se devem retornar JSON ou redirecionar.
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        return $this->isAjax() || $this->getHeader('Accept') === 'application/json';
    }

    /**
     * Retorna todos os erros como uma única string (HTML).
     *
     * @param string $separator
     * @return string
     */
    public function getAllErrorsAsString(string $separator = '<br>'): string
    {
        $allErrors = [];
        foreach ($this->errors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }
        return implode($separator, $allErrors);
    }
}