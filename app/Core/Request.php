<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Representa uma requisição HTTP.
 * 
 * Encapsula superglobals ($_GET, $_POST, $_FILES, $_SERVER, $_COOKIE)
 * e fornece uma interface segura e testável para acessar os dados da requisição.
 */
class Request
{
    private array $query;
    private array $post;
    private array $files;
    private array $server;
    private array $cookies;

    /**
     * Construtor opcional para injeção de dados (útil em testes).
     * Se nenhum parâmetro for passado, usa as superglobals.
     *
     * @param array|null $query   $_GET ou equivalente
     * @param array|null $post    $_POST ou equivalente
     * @param array|null $files   $_FILES ou equivalente
     * @param array|null $server  $_SERVER ou equivalente
     * @param array|null $cookies $_COOKIE ou equivalente
     */
    public function __construct(
        ?array $query = null,
        ?array $post = null,
        ?array $files = null,
        ?array $server = null,
        ?array $cookies = null
    ) {
        $this->query   = $query ?? $_GET;
        $this->post    = $post ?? $_POST;
        $this->files   = $files ?? $_FILES;
        $this->server  = $server ?? $_SERVER;
        $this->cookies = $cookies ?? $_COOKIE;
    }

    /**
     * Retorna o método HTTP da requisição (GET, POST, PUT, DELETE, PATCH, etc.)
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Verifica se o método HTTP é POST.
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Verifica se o método HTTP é GET.
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Verifica se é uma requisição AJAX (baseado no header HTTP_X_REQUESTED_WITH).
     */
    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Retorna o caminho da URI (sem query string).
     * Exemplo: /dashboard/veiculos
     */
    public function getPath(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Obtém um valor da query string (GET).
     *
     * @param string|null $key     Chave desejada. Se null, retorna todo o array.
     * @param mixed       $default Valor padrão se a chave não existir.
     * @return mixed
     */
    public function getQuery(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Obtém um valor do corpo da requisição (POST).
     *
     * @param string|null $key     Chave desejada. Se null, retorna todo o array.
     * @param mixed       $default Valor padrão se a chave não existir.
     * @return mixed
     */
    public function getPost(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Obtém um valor da requisição (primeiro de POST, depois GET).
     *
     * @param string $key     Chave desejada.
     * @param mixed  $default Valor padrão se a chave não existir.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Retorna apenas as chaves especificadas (extrai um subconjunto dos dados POST + GET).
     *
     * @param array $keys Lista de chaves a serem extraídas.
     * @return array
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $value = $this->get($key);
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Retorna todos os dados (GET + POST).
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Obtém um arquivo enviado ou todo o array de arquivos.
     *
     * @param string|null $key Chave desejada. Se null, retorna todos os arquivos.
     * @return array|null
     */
    public function getFile(?string $key = null): ?array
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    /**
     * Verifica se um arquivo foi enviado com sucesso para a chave informada.
     *
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Retorna o IP do cliente, respeitando proxies (útil para redes atrás de load balancer).
     */
    public function getClientIp(): string
    {
        $ip = $this->server['HTTP_CLIENT_IP']
            ?? $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['REMOTE_ADDR']
            ?? 'unknown';

        // Se for uma lista de IPs (X-Forwarded-For), pega o primeiro
        if (str_contains($ip, ',')) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }

        return $ip;
    }

    /**
     * Retorna o valor de um cabeçalho HTTP.
     *
     * @param string $name    Nome do cabeçalho (ex: 'User-Agent', 'Content-Type').
     * @param string|null $default Valor padrão se o cabeçalho não existir.
     * @return string|null
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? $default;
    }

    /**
     * Retorna todos os cabeçalhos HTTP em um array associativo.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * Obtém o conteúdo bruto do corpo da requisição (útil para APIs que enviam JSON).
     */
    public function getContent(): string
    {
        return file_get_contents('php://input');
    }

    /**
     * Decodifica o corpo como JSON e retorna como array associativo.
     * Retorna null se não for JSON válido.
     */
    public function getJson(): ?array
    {
        $content = $this->getContent();
        if (empty($content)) {
            return null;
        }
        $data = json_decode($content, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Obtém o valor de um cookie.
     *
     * @param string $key     Nome do cookie.
     * @param mixed  $default Valor padrão se não existir.
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Verifica se a requisição é segura (HTTPS).
     */
    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ($this->server['SERVER_PORT'] ?? 0) === 443;
    }

    /**
     * Retorna o host (domínio) da requisição.
     */
    public function getHost(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Retorna a URL completa da requisição (com protocolo e host).
     */
    public function getFullUrl(): string
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        $host = $this->getHost();
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return $protocol . '://' . $host . $uri;
    }
}