# Módulo de Autenticação do Administrador

## 📌 Objetivo

Este documento descreve o módulo de autenticação do administrador, incluindo fluxo de login, logout, proteção CSRF, limite de tentativas de login, logs e boas práticas de segurança implementadas.

---

## 🧩 Visão Geral

O módulo de autenticação do administrador é responsável por:

- Autenticar o administrador via e-mail e senha.
- Manter a sessão do administrador com segurança.
- Proteger rotas administrativas com middleware específico.
- Registrar logs de todas as ações (login, logout, falhas).
- Proteger contra ataques de força bruta com limite de tentativas.
- Proteger contra CSRF com token por sessão.

---

## 🔐 Componentes Principais

| Componente | Arquivo | Responsabilidade |
|------------|---------|------------------|
| **Controller** | `app/Controller/AdminAuthController.php` | Recebe requisições, renderiza views, processa login/logout. |
| **Service** | `app/Service/AdminAuthService.php` | Lógica de autenticação, verificação de bloqueio, reset de tentativas. |
| **Middleware** | `app/Middleware/AdminMiddleware.php` | Protege rotas administrativas (verifica `admin_id` na sessão). |
| **Repository** | `app/Repository/AdministradorRepository.php` | Acesso à tabela `administradores`. |
| **Repository** | `app/Repository/LoginAttemptRepository.php` | Gerencia tentativas de login (bloqueio, reset, limpeza). |
| **View** | `app/View/admin/login.php` | Formulário de login do administrador. |
| **View** | `app/View/admin/dashboard.php` | Painel administrativo inicial. |
| **View** | `app/View/erros/403.php` | Página de erro (acesso negado). |

---

## 🔄 Fluxo de Login

### 1. Acesso à página de login (`GET /admin/login`)

1. O `AdminAuthController::formLogin()` é chamado.
2. Verifica se o administrador já está logado (`check()`).
   - Se sim → redireciona para `/admin`.
   - Se não → renderiza a view `admin/login.php` com o token CSRF.
3. O formulário contém:
   - Campos: e-mail, senha.
   - Campo hidden: `csrf_token` (gerado pelo `CsrfTokenMiddleware`).

### 2. Envio do formulário (`POST /admin/login`)

1. O `AdminAuthController::login()` recebe a requisição.
2. O `CsrfValidationMiddleware` valida o token CSRF.
   - Se inválido → lança `CsrfException` → erro 403.
3. O controller captura o IP do cliente (`$request->getClientIp()`).
4. Chama `AdminAuthService::login($email, $senha, $clientIp)`.

### 3. Processamento no `AdminAuthService::login()`

#### a) Verificação de expiração do bloqueio (opcional)
- Se `blocked_until` expirou, reseta o contador automaticamente.
- Registra log `INFO`: `"Bloqueio expirado, contador de tentativas resetado"`.

#### b) Verificação de bloqueio
- Chama `LoginAttemptRepository::isBlocked($email, $ip)`.
- Se bloqueado → retorna erro `"Muitas tentativas de login. Tente novamente mais tarde."` e registra log `WARNING`: `"Tentativa de login durante bloqueio ativo"`.

#### c) Busca do administrador
- Chama `AdministradorRepository::findByEmail($email)`.

#### d) Validação de credenciais
- Se e-mail não encontrado ou senha incorreta:
  - Registra tentativa falha: `LoginAttemptRepository::recordFailedAttempt($email, $ip)`.
  - Se limite atingido (5 tentativas), ativa bloqueio por 60 minutos.
  - Registra log `WARNING`: `"Tentativa de login falhou (admin)"`.
  - Retorna erro `"E-mail ou senha inválidos."`.

#### e) Login bem-sucedido
- Regenera ID da sessão (`Session::regenerate()`).
- Armazena dados na sessão: `admin_id`, `admin_nome`, `admin_email`.
- Regenera token CSRF (`csrf_token`).
- Reseta tentativas: `LoginAttemptRepository::resetAttempts($email, $ip)`.
- Limpa registros antigos (30 dias): `LoginAttemptRepository::deleteOldRecords()`.
- Registra log `INFO`: `"Login de administrador bem-sucedido"`.
- Retorna sucesso.

### 4. Resposta do controller

- Se sucesso → redireciona para `/admin` (ou retorna JSON para AJAX).
- Se falha → redireciona para `/admin/login` com mensagem de erro (flash ou JSON).

---

## 🔐 Proteção CSRF

O módulo de autenticação do administrador utiliza a mesma proteção CSRF implementada para todo o sistema:

- **Geração:** `CsrfTokenMiddleware` garante que o token exista na sessão.
- **Validação:** `CsrfValidationMiddleware` valida o token em requisições POST.
- **Regeneração:** O token é regenerado após login bem-sucedido.
- **Suporte a AJAX:** O token pode ser enviado via cabeçalho `X-CSRF-TOKEN`.

Para mais detalhes, consulte a documentação específica: [docs/CSRF.md](./CSRF.md).

---

## 🔒 Limite de Tentativas de Login

### Comportamento

- **Limite:** 5 tentativas falhas consecutivas.
- **Bloqueio:** 60 minutos.
- **Escopo:** Combinação de `email + IP`.
- **Reset:** Tentativas são resetadas automaticamente após login bem-sucedido ou expiração do bloqueio.

### Variáveis de ambiente (`.env`)

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `MAX_LOGIN_ATTEMPTS` | Número máximo de tentativas falhas antes do bloqueio. | `5` |
| `BLOCK_DURATION_MINUTES` | Duração do bloqueio em minutos. | `60` |
| `LOGIN_ATTEMPTS_RETENTION_DAYS` | Dias de retenção de registros de tentativas. | `30` |

### Logs de tentativas

| Evento | Nível | Contexto |
|--------|-------|----------|
| Primeira tentativa falha | `DEBUG` | email, IP |
| Bloqueio ativado | `WARNING` | email, IP, attempts, blocked_until, max_attempts |
| Tentativa durante bloqueio | `WARNING` | email, IP |
| Bloqueio expirado (reset) | `INFO` | email, IP, previous_attempts |
| Reset após login bem-sucedido | `DEBUG` / `INFO` | email, IP |
| Limpeza de registros antigos | `INFO` / `DEBUG` | deleted, days |

---

## 📊 Logs de Autenticação

### Eventos registrados

| Evento | Nível | Contexto |
|--------|-------|----------|
| Login bem-sucedido | `INFO` | email, IP |
| Falha de login | `WARNING` | email, IP, motivo |
| Logout | `INFO` | email, IP |
| Tentativa durante bloqueio | `WARNING` | email, IP |

### Exemplos de logs

```json
[2026-06-19 22:31:27] app.INFO: Login de administrador bem-sucedido {"email":"admin@meucarango.com","ip":"127.0.0.1"}
```

```json
[2026-06-19 22:28:52] app.WARNING: Bloqueio ativado por excesso de tentativas {"email":"admin@meucarango.com","ip":"127.0.0.1","attempts":5,"blocked_until":"2026-06-19 23:28:52","max_attempts":5}
```

```json
[2026-06-19 22:31:27] app.INFO: Logout de administrador {"email":"admin@meucarango.com","ip":"127.0.0.1"}
```

---

## 🧪 Testes Realizados

| Cenário | Resultado | Status |
|---------|-----------|--------|
| Bloqueio após 5 tentativas falhas | Mensagem "Muitas tentativas..." | ✅ |
| Tentativa durante bloqueio | Mensagem e log `WARNING` | ✅ |
| Reset automático após expiração | Contador zerado, log `INFO` | ✅ |
| Reset após login bem-sucedido | Contador zerado, log `DEBUG` | ✅ |
| Limpeza de registros antigos (30 dias) | Registros removidos, log `INFO` | ✅ |
| Logs de autenticação | Níveis e contexto corretos | ✅ |
| CSRF (POST tradicional) | Token válido → sucesso | ✅ |
| CSRF (AJAX) | Token via cabeçalho → sucesso | ✅ |

---

## 🛠️ Manutenção e Evolução

### Como ajustar o limite de tentativas

1. Edite o arquivo `.env`:
   ```
   MAX_LOGIN_ATTEMPTS=5
   BLOCK_DURATION_MINUTES=60
   LOGIN_ATTEMPTS_RETENTION_DAYS=30
   ```
2. As alterações são aplicadas automaticamente (sem necessidade de reiniciar o servidor, apenas recarregar a página).

### Como resetar manualmente um bloqueio

```sql
UPDATE login_attempts 
SET attempts = 0, blocked_until = NULL 
WHERE email = 'admin@meucarango.com' AND ip_address = '127.0.0.1';
```

### Como visualizar tentativas de um IP

```sql
SELECT * FROM login_attempts WHERE ip_address = 'IP_AQUI';
```

---

## 🔧 Arquivos Relacionados

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Controller/AdminAuthController.php` | Controlador de autenticação do admin. |
| `app/Service/AdminAuthService.php` | Lógica de autenticação e bloqueio. |
| `app/Middleware/AdminMiddleware.php` | Middleware de proteção de rotas admin. |
| `app/Repository/AdministradorRepository.php` | Acesso à tabela `administradores`. |
| `app/Repository/LoginAttemptRepository.php` | Gerenciamento de tentativas de login. |
| `app/View/admin/login.php` | View de login. |
| `app/View/admin/dashboard.php` | Dashboard administrativo. |
| `public/index.php` | Registro de serviços e rotas. |
| `.env` | Configurações de ambiente (limites, timezone). |

---

## 📌 Observações Finais

- **Sessão:** Utiliza `SessionWrapper` e `SessionInterface` para injeção de dependência e testabilidade.
- **Logs:** Integrado com Monolog (níveis, rotação, processadores).
- **Segurança:** CSRF, limite de tentativas, sessão segura (`HttpOnly`, `Secure`, `SameSite`).
- **Fuso horário:** Recomenda-se manter PHP e MySQL em UTC para consistência.
- **Suporte AJAX:** O login suporta requisições AJAX com respostas JSON.
