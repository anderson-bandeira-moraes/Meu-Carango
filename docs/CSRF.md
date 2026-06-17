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
   - Compara com o token armazenado na sessão.
   - Se **válido** → requisição prossegue para o controller.
   - Se **inválido** → lança `ForbiddenException` (erro 403).
6. **Resposta**:
   - Sucesso: ação é executada (ex: login, criação de veículo).
   - Erro: view `erros/403.php` é renderizada com mensagem amigável.

---

## 🔐 Regeneração do Token

- **Após login bem-sucedido** (lojista e administrador):
  - O token CSRF é **regenerado** para evitar fixação de token (reutilização de token pré-login).
  - Nova geração: `Session::set('csrf_token', bin2hex(random_bytes(32)))`.

**Por que isso é importante?**
- O token usado no formulário de login é substituído por um novo após autenticação, tornando-o inútil para um eventual atacante que o tenha interceptado antes do login.

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