# Módulo 2FA (Autenticação em Duas Etapas) – Administrador

## 📌 Objetivo

Adicionar uma camada extra de segurança ao login do administrador, exigindo um código de uso único enviado por e-mail após a senha correta. Isso protege a conta administrativa mesmo se a senha for comprometida.

---

## 🔄 Fluxo Completo

### Ciclo de vida de uma requisição 2FA:

1. **Login com credenciais corretas** (`/admin/login`) → `AdminAuthService::login()` valida a senha.
2. **Início do fluxo 2FA** → Dados do admin são armazenados na sessão (`pending_admin_*`).
3. **Geração do código** → `TwoFactorService::generateAndSend()`:
   - Verifica se o bloqueio expirou (`resetCounterIfBlockedExpired`).
   - Verifica se o último código foi gerado há mais de 30 minutos (via SQL `TIMESTAMPDIFF`). Se sim, reseta `resend_count`.
   - Gera código de 6 dígitos, salva no banco (`TwoFactorRepository::save`).
   - Incrementa `resend_count` (login conta como 1 envio).
   - Envia e-mail via `MailService::sendTwoFactorCode`.
4. **Redirecionamento para `/admin/2fa`** → Usuário vê o formulário para inserir o código.
5. **Verificação do código** (`POST /admin/2fa/verify`) → `TwoFactorService::verify()`:
   - Verifica se o registro existe e não expirou.
   - Verifica se o código coincide.
   - Se válido → remove o registro, define `admin_id` e `2fa_verified` na sessão, redireciona para `/admin`.
   - Se inválido → incrementa `attempts`. Após 3 tentativas, retorna `exhausted` (sem remover o registro).
6. **Reenvio de código** (`POST /admin/2fa/resend`) → `TwoFactorService::resend()`:
   - Verifica se o bloqueio expirou (`resetCounterIfBlockedExpired`).
   - Verifica se `blocked_until` está ativo (`isBlockedFromResend`).
   - Se permitido, gera novo código, incrementa `resend_count` e reenvia e-mail.
   - Se `resend_count >= 3`, ativa bloqueio de 30 minutos (`blocked_until`).
7. **Bloqueio de reenvio** – Ao clicar em "Reenviar" durante o bloqueio (4ª tentativa), exibe **modal com contagem regressiva** e redireciona para `/admin/login`.

---

## 🗄️ Estrutura de Dados

### Tabela: `two_factor_codes`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | int unsigned | Chave primária. |
| `email` | varchar(100) | E-mail do administrador (único). |
| `code` | varchar(6) | Código de 6 dígitos. |
| `expires_at` | datetime | Data/hora de expiração do código. |
| `attempts` | tinyint unsigned | Número de tentativas de verificação (máx. 3). |
| `resend_count` | tinyint unsigned | Número de reenvios realizados (máx. 3). |
| `blocked_until` | datetime | Data/hora até quando o reenvio está bloqueado. |
| `created_at` | datetime | Data/hora de criação do registro (geração do código). |
| `updated_at` | datetime | Data/hora da última atualização. |

**Índices:**
- `PRIMARY KEY (id)`
- `UNIQUE KEY email (email)`
- `KEY expires_at (expires_at)`

---

## 🧩 Componentes do Módulo

### 1. Repositório (`TwoFactorRepository`)

**Arquivo:** `app/Repository/TwoFactorRepository.php`

**Responsabilidade:** Persistência e consultas à tabela `two_factor_codes`.

| Método | Descrição |
|--------|-----------|
| `save(string $email, string $code, int $expiryMinutes)` | Insere ou atualiza (upsert) o código. |
| `findByEmail(string $email): ?array` | Busca o registro ativo para o e-mail. |
| `incrementAttempts(string $email)` | Incrementa o contador de tentativas de verificação. |
| `incrementResendCount(string $email, int $maxResend, int $blockMinutes)` | Incrementa reenvios e ativa bloqueio se limite atingido. |
| `reset(string $email)` | Remove o registro (após login bem-sucedido). |
| `deleteExpired()` | Remove registros expirados (limpeza automática). |
| `isBlockedFromResend(string $email): bool` | Verifica se o reenvio está bloqueado. |
| `getAttempts(string $email): int` | Retorna o número atual de tentativas. |
| `resetResendCounter(string $email)` | Zera `resend_count`, remove `blocked_until` e atualiza `created_at`. |
| `getMinutesSinceCreation(string $email): ?int` | Retorna minutos decorridos desde a criação do registro (via SQL `TIMESTAMPDIFF`). |

---

### 2. Serviço de E-mail (`MailService`)

**Arquivo:** `app/Service/MailService.php`

**Responsabilidade:** Encapsular o envio de e-mails via SMTP (PHPMailer).

| Método | Descrição |
|--------|-----------|
| `send(string $to, string $subject, string $body, bool $isHtml): bool` | Envia e-mail genérico. |
| `sendTwoFactorCode(string $to, string $code, int $expiryMinutes): bool` | Envia e-mail específico para 2FA com template HTML. |
| `isConfigured(): bool` | Verifica se as configurações SMTP estão completas. |

**Template do e-mail:**
- Corpo HTML com CSS nativo (sem Bootstrap).
- Exibe o código de 6 dígitos em destaque.
- Informa o tempo de validade (configurável via `.env`).
- Mensagem de segurança: "Se você não solicitou este código, ignore este e-mail."

---

### 3. Serviço 2FA (`TwoFactorService`)

**Arquivo:** `app/Service/TwoFactorService.php`

**Responsabilidade:** Lógica de negócio do 2FA (geração, verificação, reenvio, status).

| Método | Descrição |
|--------|-----------|
| `generateAndSend(string $email): array` | Gera código, salva no banco, incrementa `resend_count` e envia e-mail. |
| `verify(string $email, string $code): array` | Valida o código com controle de tentativas (`attempts`). |
| `resend(string $email): array` | Gera novo código, incrementa `resend_count` e gerencia bloqueio. |
| `getStatus(string $email): array` | Retorna status atual (existe, expiração, tentativas, bloqueio). |
| `reset(string $email)` | Remove o registro manualmente. |
| `cleanup()` | Remove registros expirados. |
| `getRecord(string $email): ?array` | Retorna o registro completo. |
| `isResendBlocked(string $email): bool` | Verifica se o reenvio está bloqueado. |

**Regras de negócio:**

| Regra | Valor | Configurável via `.env` |
|-------|-------|--------------------------|
| Expiração do código | 5 minutos | `TWO_FACTOR_EXPIRY_MINUTES` |
| Máximo de tentativas de verificação | 3 | `TWO_FACTOR_MAX_ATTEMPTS` |
| Máximo de reenvios permitidos | 3 | `TWO_FACTOR_MAX_RESEND` |
| Bloqueio de reenvio após limite | 30 minutos | `TWO_FACTOR_RESEND_BLOCK_MINUTES` |

**Reset do `resend_count`:**
- **Por expiração do bloqueio:** quando `blocked_until < NOW()`, o contador é resetado para 0.
- **Por código antigo (> 30 min):** se `created_at` for superior a 30 minutos, o contador é resetado para 0 (via `TIMESTAMPDIFF` no SQL).
- **Por login bem-sucedido:** o registro é removido (não apenas resetado).

---

### 4. Controlador 2FA (`TwoFactorController`)

**Arquivo:** `app/Controller/TwoFactorController.php`

**Responsabilidade:** Orquestrar requisições HTTP para o fluxo 2FA.

| Método | Rota | Função |
|--------|------|--------|
| `form(Request $request): string` | `GET /admin/2fa` | Exibe formulário para inserir o código. |
| `verify(Request $request): void` | `POST /admin/2fa/verify` | Valida o código. Se válido, conclui login. Se inválido, incrementa tentativas e redireciona. |
| `resend(Request $request): void` | `POST /admin/2fa/resend` | Gera novo código, reenvia e gerencia bloqueio (exibe modal se bloqueado). |

**Detalhes do `verify()`:**
- Verifica se o código existe e não expirou.
- Se `attempts >= 3`, retorna `exhausted` (mantém o registro).
- Se código válido, remove registro, define `admin_id` e `2fa_verified` na sessão.

**Detalhes do `resend()`:**
- Se bloqueado (`blocked_until > NOW()`), define `show_blocked_modal = true` na sessão.
- A view exibe o modal com contagem regressiva (5 segundos) e redireciona para `/admin/login`.

---

### 5. Middleware 2FA (`TwoFactorMiddleware`)

**Arquivo:** `app/Middleware/TwoFactorMiddleware.php`

**Responsabilidade:** Proteger todas as rotas do grupo `/admin`, garantindo que o 2FA foi concluído.

| Lógica | Ação |
|--------|------|
| Rota ignorada (whitelist) | `/admin/login`, `/admin/logout`, `/admin/2fa` (e sub-rotas). |
| `admin_id` não existe | Redireciona para `/admin/login`. |
| `2fa_verified` não existe | Redireciona para `/admin/2fa`. |
| `admin_id` e `2fa_verified` existem | Permite acesso. |

**Aplicação no `index.php`:** O middleware é adicionado ao grupo `/admin`, após `AdminMiddleware`.

```php
$router->group('/admin', function(Router $router) use ($container) {
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    $router->middleware($container->get(AdminMiddleware::class));
    $router->middleware($container->get(TwoFactorMiddleware::class));
    $router->middleware($container->get(CsrfValidationMiddleware::class));
    // ... rotas administrativas
});
```

---

### 6. View `admin/2fa.php`

**Arquivo:** `app/View/admin/2fa.php`

**Responsabilidade:** Exibir formulário para inserção do código e gerenciar feedback visual.

**Elementos:**
- Mensagens flash (erro/sucesso).
- Instruções e e-mail do destinatário.
- Campo de código com validação HTML5 (`[0-9]{6}`).
- Botão "Verificar" (desabilitado se código expirado ou inexistente).
- Botão "Reenviar código" (sempre clicável).
- Botão "Login" (redireciona para `/admin/logout`).

**Modal de bloqueio:**
- Exibido quando `$showModal = true` (após clique em reenviar durante bloqueio).
- Contagem regressiva de 5 segundos.
- Redireciona para `/admin/login` ao final da contagem.
- Botão "Cancelar" permite permanecer na página.

---

### 7. Integração com `AdminAuthService`

**Arquivo:** `app/Service/AdminAuthService.php`

**Modificações no método `login()`:**

| Antes | Depois |
|-------|--------|
| Após senha correta, conclui login imediatamente. | Após senha correta, verifica se há bloqueio de reenvio ativo (`blocked_until > NOW()`). Se ativo, bloqueia login e exibe mensagem. |
| Retorna `['sucesso' => true]`. | Se bloqueio inativo, armazena dados pendentes (`pending_admin_*`), chama `TwoFactorService::generateAndSend()` e retorna `['sucesso' => true, '2fa_required' => true]`. |

**Código relevante:**

```php
// Verifica se há bloqueio de reenvio ativo na tabela two_factor_codes
$twoFactorRecord = $this->twoFactorService->getRecord($email);
if ($twoFactorRecord && $twoFactorRecord['blocked_until'] !== null && strtotime($twoFactorRecord['blocked_until']) > time()) {
    $minutesLeft = ceil((strtotime($twoFactorRecord['blocked_until']) - time()) / 60);
    return [
        'sucesso' => false,
        'erro'    => "Você está bloqueado para reenviar códigos de verificação. Aguarde {$minutesLeft} minutos para tentar novamente.",
    ];
}

// Senha correta: inicia fluxo 2FA
$this->session->set('pending_admin_id', $admin['id']);
$this->session->set('pending_admin_email', $admin['email']);
$this->session->set('pending_admin_nome', $admin['nome']);
$result = $this->twoFactorService->generateAndSend($admin['email']);
```

---

## ⚙️ Configuração

### Variáveis de ambiente (`.env`)

```env
# ============== CONFIGURAÇÃO 2FA ==============
TWO_FACTOR_EXPIRY_MINUTES=5
TWO_FACTOR_MAX_ATTEMPTS=3
TWO_FACTOR_MAX_RESEND=3
TWO_FACTOR_RESEND_BLOCK_MINUTES=30

# ============== CONFIGURAÇÃO SMTP ==============
MAIL_HOST=smtp.gmail.com            # ou smtp.office365.com, etc.
MAIL_PORT=587
MAIL_USERNAME=seuemail@gmail.com
MAIL_PASSWORD=senha_app             # Senha de aplicativo
MAIL_ENCRYPTION=tls
MAIL_FROM=seuemail@gmail.com
MAIL_FROM_NAME=Meu Carango
MAIL_TIMEOUT=30
```

---

## 🔐 Segurança

| Prática | Implementação |
|---------|---------------|
| **CSRF** | Campos hidden `csrf_token` nos formulários POST, middleware `CsrfValidationMiddleware`. |
| **Limite de tentativas** | Máximo de 3 tentativas de verificação; após esgotar, retorna `exhausted` sem remover registro. |
| **Bloqueio de reenvio** | Máximo de 3 reenvios; bloqueio de 30 minutos. |
| **Persistência do bloqueio** | `blocked_until` é mantido no banco; login é bloqueado enquanto ativo. |
| **Reset do contador** | `resend_count` é resetado após 30 minutos de inatividade (`TIMESTAMPDIFF` no SQL). |
| **Logs** | Todos os eventos (sucesso, falha, bloqueio) são registrados com níveis `INFO`, `WARNING`, `ERROR`, `DEBUG`. |
| **Mensagens genéricas** | Erros não expõem detalhes internos (ex: "Código inválido", "Reenvio bloqueado"). |
| **Sessão segura** | Cookies com `HttpOnly`, `Secure`, `SameSite`, regeneração de ID após login. |
| **E-mail seguro** | Senhas não são logadas; envio via SMTP com TLS. |

---

## 📊 Logs e Monitoramento

| Evento | Nível | Contexto |
|--------|-------|----------|
| Código 2FA gerado/enviado | `INFO` | email |
| Código inválido | `WARNING` | email, attempts_left |
| Tentativas esgotadas | `WARNING` | email |
| Reenvio bem-sucedido | `INFO` | email, resend_count |
| Reenvio bloqueado (modal) | `INFO` | email, blocked_until |
| Tentativa de reenvio durante bloqueio | `WARNING` | email, blocked_until |
| Login durante bloqueio de reenvio | `WARNING` | email, blocked_until, minutes_left |
| Verificação bem-sucedida | `INFO` | email |
| Login concluído com 2FA | `INFO` | email |
| Contador resetado (bloqueio expirado) | `INFO` | email, blocked_until |
| Contador resetado (código > 30 min) | `INFO` | email, minutes_since_creation |
| Acesso negado (2FA pendente) | `WARNING` | uri, admin_id |
| Acesso permitido via 2FA | `DEBUG` | uri, admin_id |

---

## 🧪 Testes Validados

| Fase | Teste | Status |
|------|-------|--------|
| 1 | Fluxo de sucesso (login com 2FA) | ✅ |
| 1 | Login via AJAX com 2FA | ✅ |
| 2 | Código inválido (1ª, 2ª, 3ª tentativas) | ✅ |
| 2 | Esgotamento de tentativas | ✅ |
| 3 | Código expira (reenvio disponível) | ✅ |
| 3 | Código expira (reenvio bloqueado) | ✅ |
| 4 | Reenvio (1º, 2º, 3º) | ✅ |
| 4 | Quarto reenvio (bloqueado – modal) | ✅ |
| 4 | Reenvio após bloqueio expirar | ✅ |
| 5 | Login durante bloqueio ativo | ✅ |
| 5 | Login após bloqueio expirar | ✅ |
| 6 | Força bruta (múltiplas tentativas) | ✅ |
| 6 | Código reutilizado | ✅ |
| 6 | Código exposto em logs | ✅ |
| 7 | Formulário durante bloqueio | ✅ |
| 7 | Mensagens flash claras | ✅ |
| 7 | Cancelar verificação | ✅ |
| 7 | Foco automático no campo de código | ✅ |
| 8 | Login com credenciais inválidas | ✅ |
| 8 | Login com credenciais corretas (2FA ativo) | ✅ |
| 8 | Login após logout | ✅ |
| 9 | Logs (geração, inválido, esgotamento, bloqueio, login, verificação) | ✅ |
| 10 | Registro expirado – limpeza automática | ✅ |
| 10 | Registro removido após sucesso | ✅ |

---

## 📂 Arquivos Relacionados

### Diretos (criados para o 2FA)

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Controller/TwoFactorController.php` | Controlador HTTP. |
| `app/Service/TwoFactorService.php` | Lógica de negócio 2FA. |
| `app/Service/MailService.php` | Envio de e-mails via SMTP. |
| `app/Repository/TwoFactorRepository.php` | Persistência no banco. |
| `app/Middleware/TwoFactorMiddleware.php` | Proteção de rotas. |
| `app/View/admin/2fa.php` | View do formulário e modal. |
| `docs/2fa.md` | Documentação (este arquivo). |

### Indiretos (modificados ou dependentes)

| Arquivo | Modificação / Dependência |
|---------|---------------------------|
| `app/Service/AdminAuthService.php` | Integração com 2FA (início do fluxo). |
| `app/Controller/AdminAuthController.php` | Ajuste para redirecionar para `/admin/2fa`. |
| `app/View/admin/login.php` | Remoção de mensagens obsoletas. |
| `public/index.php` | Registro de serviços, rotas e middleware. |
| `config/logging.php` | Configuração de logs (já existente). |
| `app/Helpers/csrf.php` | Uso nas views. |
| `.env` | Configurações SMTP e 2FA. |
| `composer.json` / `composer.lock` | Inclusão de `phpmailer/phpmailer`. |

---

## 📌 Observações Finais

- **Token por sessão, armazenado no banco:** o código 2FA é persistido, permitindo que o usuário saia e retorne sem perder o estado.
- **`resend_count` é preservado entre ciclos:** o login não reseta o contador; apenas a expiração do código (5 min) ou inatividade > 30 min o faz.
- **Modal com contagem regressiva:** substitui avisos fixos, oferecendo feedback claro e redirecionamento automático.
- **Logs completos:** todas as ações são registradas, facilitando auditoria e depuração.
- **Middleware 2FA:** protege todas as rotas administrativas, garantindo que o 2FA seja concluído antes do acesso.
