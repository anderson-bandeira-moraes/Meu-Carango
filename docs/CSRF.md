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
   - Se **inválido** → registra log `WARNING` com IP, URI e método; lança `ForbiddenException` (erro 403).
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