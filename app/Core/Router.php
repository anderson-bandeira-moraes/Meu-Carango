<?php

declare(strict_types=1);

namespace App\Core;

use App\Exception\HttpNotFoundException;

class Router
{
    /**
     * Rotas registradas.
     * @var array<int, array{método: string, padrão: string, callback: callable|array, middleware: array}>
     */
    private array $routes = [];

    /**
     * Prefixo de caminho atual (usado em grupo).
     */
    private string $prefix = '';

    /**
     * Middleware do grupo atual.
     * @var array<callable>
     */
    private array $middlewareStack = [];

    public function __construct(private Container $container)
    {
    }

    /**
     * Registra uma rota GET.
     *
     * @param string $pattern  Ex: '/loja/{slug}'
     * @param string $handler  'Controller@metodo'
     */
    public function get(string $pattern, string $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Registra uma rota POST.
     */
    public function post(string $pattern, string $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Agrupa rotas com prefixo e middleware compartilhados.
     *
     * @param string   $prefix    Ex: '/dashboard'
     * @param callable $callback  Função que recebe o Router e define as rotas.
     */
    public function group(string $prefix, callable $callback): self
    {
        // Salva estado atual
        $previousPrefix = $this->prefix;
        $previousStack  = $this->middlewareStack;

        // Aplica o prefixo e (opcionalmente) middleware — o middleware é injetado via ->middleware() no retorno
        $this->prefix .= $prefix;

        $callback($this);

        // Restaura estado anterior
        $this->prefix         = $previousPrefix;
        $this->middlewareStack = $previousStack;

        return $this;
    }

    /**
     * Define um middleware para o grupo de rotas.
     *
     * @param object $middleware Objeto com método handle().
     */
    public function middleware(object $middleware): self
    {
        $this->middlewareStack[] = $middleware;
        return $this;
    }

    /**
     * Adiciona uma rota à lista.
     */
    private function addRoute(string $method, string $pattern, string $handler): self
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $this->prefix . $pattern,
            'callback'   => $handler,
            'middleware' => $this->middlewareStack,
        ];

        return $this;
    }

    /**
     * Despacha a requisição atual.
     *
     * @param Request $request Objeto com os dados da requisição HTTP
     *
     * @throws HttpNotFoundException Se nenhuma rota corresponder.
     */
    public function dispatch(Request $request): void
    {
        $httpMethod = $request->getMethod();
        $uri = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $httpMethod) {
                continue;
            }

            $params = $this->match($route['pattern'], $uri);

            if ($params !== false) {
                // Executa middleware da rota (se houver)
                foreach ($route['middleware'] as $mw) {
                    $mw->handle($request);
                }

                $this->execute($route['callback'], $params, $request);
                return; 
            }
        }

        throw new HttpNotFoundException("Nenhuma rota encontrada para {$httpMethod} {$uri}");
    }

    /**
     * Verifica se a URI bate com o padrão e extrai os placeholders.
     *
     * @param string $pattern Ex: '/loja/{slug}/anuncio/{id}'
     * @param string $uri     Ex: '/loja/minha-loja/anuncio/5'
     *
     * @return false|array Retorna array associativo com os valores ou false.
     */
    private function match(string $pattern, string $uri): false|array
    {
        // Remove barra final do padrão para consistência
        $pattern = rtrim($pattern, '/') ?: '/';

        // Converte placeholders {nome} em grupos nomeados regex
        $regex = preg_replace('/\{([a-z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Filtra apenas as chaves nomeadas
            return array_filter($matches, function($key) {
                return is_string($key);
            }, ARRAY_FILTER_USE_KEY);
        }

        return false;
    }

    /**
     * Executa um handler (Controller@metodo) com os parâmetros.
     *
     * @param string $handler Ex: 'VitrineController@listar'
     * @param array  $params  Parâmetros extraídos da URL
     */
    private function execute(string $handler, array $params, Request $request): void
    {
        [$controllerName, $method] = explode('@', $handler);

        $controllerClass = "App\\Controller\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller não encontrado: {$controllerClass}");
        }

        $controller = $this->container->get($controllerClass);

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Método {$method} não existe em {$controllerClass}");
        }

        // Chama o método do controller e captura o retorno
        $response = call_user_func_array([$controller, $method], array_merge([$request], $params));

        // Se o retorno for uma string, envia como resposta (HTML)
        if (is_string($response)) {
            echo $response;
        }
        // Se for void ou null, nada é feito (caso de redirects que já enviaram header e exit)
    }
}