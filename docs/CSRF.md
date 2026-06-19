# Proteção CSRF (Cross-Site Request Forgery)

## 📌 Objetivo
Proteger a aplicação contra ataques CSRF, garantindo que requisições que alteram o estado do servidor (POST, PUT, DELETE) sejam originadas de formulários legítimos da própria aplicação, prevenindo que um site malicioso force o navegador do usuário a executar ações indesejadas.

---

## 🧩 Abordagem Escolhida

- **Token por sessão:** um único token por sessão de usuário (reutilizado em múltiplos formulários durante a mesma sessão).
- **Dois middlewares separados:**
  - `CsrfTokenMiddleware` → responsável pela **geração condicional** do token (apenas onde necessário).
  - `CsrfValidationMiddleware` → responsável pela **validação** do token em requisições POST, PUT, DELETE.
- **Helper global `csrf_token()`:** usado nas views para exibir o token no campo hidden dos formulários.
- **Regeneração após login:** o token é renovado automaticamente após o login (lojista e administrador).
- **Suporte a AJAX:** o token pode ser enviado via cabeçalho `X-CSRF-TOKEN` (além do campo hidden).
- **Logs de segurança:** tentativas de CSRF inválidas são registradas com nível `WARNING`, incluindo IP, URI e método HTTP.
- **Exceção específica:** `CsrfException` (herda de `ForbiddenException`) permite tratamento diferenciado entre erros CSRF e outros erros de permissão.

---

## 🔄 Fluxo Completo do CSRF

### Ciclo de vida de uma requisição protegida:

1. **Acesso a uma rota protegida** (ex: `/admin`, `/dashboard`, `/login`).
2. **`CsrfTokenMiddleware`** é executado:
   - Verifica se `Session::has('csrf_token')`.
   - Se não existir, gera um token com `bin2hex(random_bytes(32))` e armazena na sessão.
3. **View é renderizada**:
   - O helper `csrf_token()` retorna o token da sessão.
   - O token é inserido como campo hidden no formulário:  
     `<input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">`
4. **Usuário submete o formulário** (ou faz requisição AJAX).
5. **`CsrfValidationMiddleware`** é executado:
   - Obtém o token enviado (via POST `csrf_token` ou cabeçalho `X-CSRF-TOKEN`).
   - Compara com o token armazenado na sessão usando `hash_equals()` (timing-safe).
   - Se **válido** → requisição prossegue para o controller.
   - Se **inválido** → registra log `WARNING` com IP, URI e método; lança `CsrfException` (erro 403).
6. **Resposta**:
   - Sucesso: ação é executada (ex: login, criação de veículo).
   - Erro: view `erros/403.php` é renderizada com mensagem amigável (ou JSON para AJAX).

---

## 🔐 Regeneração do Token

- **Após login bem-sucedido** (lojista e administrador):
  - O token CSRF é **regenerado** para evitar fixação de token (reutilização de token pré-login).
  - Nova geração: `Session::set('csrf_token', bin2hex(random_bytes(32)))`.

**Por que isso é importante?**
- O token usado no formulário de login é substituído por um novo após autenticação, tornando-o inútil para um eventual atacante que o tenha interceptado antes do login.

**Comportamento com múltiplas abas:**
- O token é compartilhado entre abas (mesma sessão).
- Após regeneração (login em uma aba), o token antigo em outras abas é invalidado, resultando em erro 403 ao tentar enviar formulários sem recarregar a página.

---

## 📝 Implementação Técnica

### Arquivos criados/modificados

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Middleware/CsrfTokenMiddleware.php` | Geração condicional do token. |
| `app/Middleware/CsrfValidationMiddleware.php` | Validação do token (com log). |
| `app/Helpers/csrf.php` | Helper global `csrf_token()`. |
| `app/Core/Contracts/CsrfTokenGeneratorInterface.php` | Interface para geração de token (abstração). |
| `app/Core/Security/CsrfTokenGenerator.php` | Implementação concreta usando `random_bytes()`. |
| `app/Exception/CsrfException.php` | Exceção específica para erros CSRF (herda de `ForbiddenException`). |
| `app/View/admin/login.php` | Campo hidden no formulário de login + modal de escolha (POST/AJAX). |
| `app/View/erros/403.php` | Página de erro amigável. |

### Registro no container (`index.php`)

```php
use Monolog\Logger;

// CSRF Middlewares
$container->set(CsrfTokenGeneratorInterface::class, function() {
    return new CsrfTokenGenerator();
});

$container->set(CsrfTokenMiddleware::class, function($c) {
    return new CsrfTokenMiddleware(
        $c->get(SessionInterface::class),
        $c->get(CsrfTokenGeneratorInterface::class)
    );
});

$container->set(CsrfValidationMiddleware::class, function($c) {
    return new CsrfValidationMiddleware(
        $c->get(SessionInterface::class),
        $c->get(Logger::class) // Logger injetado para logs de CSRF
    );
});
```

### Tratamento de exceções no `index.php`

```php
} catch (\App\Exception\CsrfException $e) {
    http_response_code(403);

    // Log específico para CSRF
    $logger->warning('Acesso negado por CSRF', [
        'uri'    => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'message'=> $e->getMessage(),
    ]);

    if ($request->isAjax()) {
        echo json_encode(['erro' => $e->getMessage(), 'status' => 403]);
        exit;
    }
    echo $view->render('erros/403', ['mensagem' => $e->getMessage()]);

} catch (\App\Exception\ForbiddenException $e) {
    http_response_code(403);

    // Log específico para permissão (não CSRF)
    $logger->warning('Acesso negado por permissão', [
        'uri'    => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'message'=> $e->getMessage(),
    ]);

    // ... resposta (HTML/JSON)
}
```

---

## 📊 Logs de CSRF

### Registro de tentativas inválidas

No `CsrfValidationMiddleware`, quando um token é rejeitado:

```php
$this->logger->warning('CSRF token inválido', [
    'ip'     => $request->getClientIp(),
    'uri'    => $request->getPath(),
    'method' => $request->getMethod(),
]);
```

**Os logs são armazenados em:** `storage/logs/app-YYYY-MM-DD.log`

### Logs de erro 403 no `index.php`

- **CSRF (`CsrfException`):** `Acesso negado por CSRF` – indica token inválido.
- **Permissão (`ForbiddenException`):** `Acesso negado por permissão` – indica falta de autorização (ex: admin não logado).

---

## 🧪 Testes Realizados

| Cenário | Resultado Esperado | Status |
|---------|---------------------|--------|
| **Formulários simultâneos (duas abas)** | Ambos os envios bem-sucedidos | ✅ |
| **Regeneração após login (duas abas)** | Segunda aba falha com 403 | ✅ |
| **Token forjado (modificado via DevTools)** | Erro 403 | ✅ |
| **Token ausente** | Erro 403 | ✅ |
| **Login via AJAX** | Sucesso com token via cabeçalho | ✅ |
| **Log de CSRF inválido** | Registro no arquivo de log com IP, URI, método | ✅ |
| **Log 403 diferenciado** | CSRF e permissão têm logs separados | ✅ |

---

## 🧰 Como Adicionar CSRF em Novas Rotas

### 1. Aplicar os middlewares aos grupos de rotas (no `index.php`)

```php
$router->group('/novo-grupo', function(Router $router) use ($container) {
    // 1. Geração do token
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    
    // 2. Autenticação (se necessário)
    $router->middleware($container->get(AuthMiddleware::class));
    
    // 3. Validação do token (apenas POST, PUT, DELETE)
    $router->middleware($container->get(CsrfValidationMiddleware::class));
    
    // Suas rotas aqui
    $router->get('/formulario', 'Controller@form');
    $router->post('/formulario', 'Controller@processar');
});
```

### 2. Adicionar campo hidden nas views com formulários POST

```php
<form method="POST" action="/rota">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <!-- outros campos -->
    <button type="submit">Enviar</button>
</form>
```

### 3. Suporte a AJAX

```javascript
const csrfToken = '<?= csrf_token() ?>';

fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({ ... })
});
```

---

## 🔧 Manutenção e Evolução

### Verificações periódicas
- Verificar se os arquivos de log (`storage/logs/`) não estão crescendo excessivamente (retenção de 30 dias configurada).
- Monitorar logs de CSRF para identificar padrões de ataque.
- Em produção, manter o nível de log como `WARNING` para evitar poluição com informações triviais.

### Como desabilitar temporariamente (não recomendado em produção)
- Remover os middlewares dos grupos de rotas no `index.php`.
- Ou definir `$_ENV['APP_ENV'] = 'development'` e ajustar níveis de log.

### Adicionar novas rotas protegidas
- Seguir o padrão estabelecido: incluir `CsrfTokenMiddleware` para GETs que exibem formulários e `CsrfValidationMiddleware` para POSTs.

---

## ✅ Critérios de Aceitação

- [x] Token CSRF é gerado automaticamente ao acessar rotas protegidas.
- [x] Token é exibido corretamente nas views via helper `csrf_token()`.
- [x] Formulários com token válido são processados com sucesso.
- [x] Formulários com token inválido recebem erro 403.
- [x] Token é regenerado após login (admin e lojista).
- [x] Suporte a requisições AJAX via cabeçalho `X-CSRF-TOKEN`.
- [x] Middlewares aplicados corretamente aos grupos de rotas.
- [x] Logs de CSRF são registrados com IP, URI e método.
- [x] Mensagem de erro 403 é amigável e genérica (sem expor detalhes internos).
- [x] Exceção específica `CsrfException` para tratamento diferenciado.
- [x] Logs 403 diferenciados para CSRF e permissão.

---

## 📌 Observações Finais

- **Token por sessão** é suficiente para a maioria dos casos; token por solicitação pode ser adicionado futuramente para ações críticas.
- O helper `csrf_token()` **não gera token**, apenas retorna o existente – a geração é exclusiva do middleware.
- A regeneração após login é uma camada extra de segurança que não prejudica a experiência do usuário.
- O suporte a AJAX desde já prepara o sistema para futuras funcionalidades interativas.
- Os logs de CSRF auxiliam no monitoramento de tentativas de ataque e depuração de problemas.
- A exceção `CsrfException` permite tratamento específico e logs diferenciados no `index.php`.
