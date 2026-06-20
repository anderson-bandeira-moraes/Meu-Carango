# 2FA – Autenticação em Duas Etapas (Administrador)

## 📌 Objetivo

Adicionar uma camada extra de segurança ao login do administrador, exigindo um código de uso único enviado por e-mail após a senha correta. Isso protege a conta administrativa mesmo se a senha for comprometida.

---

## 🧩 Visão Geral

O 2FA é ativado automaticamente após o administrador digitar a senha correta. Um código de 6 dígitos é gerado e enviado para o e-mail cadastrado. O administrador deve inserir esse código em uma página de verificação para concluir o login.

**Características principais:**

- Código de 6 dígitos numéricos (gerado aleatoriamente).
- Expiração configurável (padrão: 5 minutos).
- Limite de tentativas de verificação (padrão: 3).
- Reenvio de código com limite (padrão: 3 reenvios) e bloqueio após exceder (30 minutos).
- Armazenamento em banco de dados (tabela `two_factor_codes`).
- Logs de auditoria para todas as etapas.
- Proteção de rotas via middleware (`TwoFactorMiddleware`).

---

## 🔄 Fluxo Completo

### 1. Login (senha correta)

- O administrador insere e-mail e senha no formulário `/admin/login`.
- O `AdminAuthService::login()` valida as credenciais.
- Se a senha estiver correta:
  - Armazena dados pendentes na sessão (`pending_admin_id`, `pending_admin_email`, `pending_admin_nome`).
  - Gera um código 2FA via `TwoFactorService::generateAndSend()`.
  - Envia o código por e-mail.
  - Retorna `['sucesso' => true, '2fa_required' => true, 'redirect' => '/admin/2fa']`.

### 2. Exibição do formulário 2FA

- O administrador é redirecionado para `/admin/2fa`.
- O `TwoFactorController::form()` exibe o formulário com:
  - Campo para o código de 6 dígitos.
  - E-mail do destinatário.
  - Status atual (tentativas restantes, tempo de expiração, bloqueio de reenvio).
  - Link para reenviar o código.

### 3. Verificação do código

- O administrador insere o código e submete o formulário (POST `/admin/2fa/verify`).
- O `TwoFactorController::verify()` chama `TwoFactorService::verify()`.
- Se o código estiver **correto** (não expirado, dentro do limite de tentativas):
  - Remove o registro da tabela `two_factor_codes`.
  - Remove pendências da sessão.
  - Regenera o ID da sessão.
  - Define `admin_id`, `admin_nome`, `admin_email` e `2fa_verified = true` na sessão.
  - Redireciona para `/admin` (dashboard).

### 4. Se o código estiver **incorreto**:

- Incrementa o contador de tentativas no banco.
- Se o número de tentativas atingir o limite (3), o registro é removido e o usuário é redirecionado para `/admin/login` (forçando novo login).
- Caso contrário, exibe mensagem de erro com o número de tentativas restantes.

### 5. Reenvio de código

- O administrador pode solicitar um novo código via POST `/admin/2fa/resend`.
- O `TwoFactorController::resend()` chama `TwoFactorService::resend()`.
- Se o limite de reenvios não foi atingido e não há bloqueio ativo:
  - Gera novo código.
  - Atualiza o registro no banco (nova expiração, incrementa `resend_count`).
  - Envia o novo código por e-mail.
  - Se o limite de reenvios for atingido (3), define `blocked_until = NOW() + 30 minutos`.
  - Exibe mensagem de sucesso (ou aviso de bloqueio).

---

## 🧠 Regras de Negócio

| Regra | Valor | Configurável via `.env` |
|-------|-------|--------------------------|
| Expiração do código | 5 minutos | `TWO_FACTOR_EXPIRY_MINUTES` |
| Máximo de tentativas de verificação | 3 | `TWO_FACTOR_MAX_ATTEMPTS` |
| Máximo de reenvios permitidos | 3 | `TWO_FACTOR_MAX_RESEND` |
| Bloqueio de reenvio após limite | 30 minutos | `TWO_FACTOR_RESEND_BLOCK_MINUTES` |

**Comportamento detalhado:**

- Após **3 tentativas de verificação** falhas, o registro é removido e o usuário deve fazer login novamente.
- Após **3 reenvios**, o reenvio é bloqueado por **30 minutos**.
- O código expira automaticamente após o tempo configurado.
- Registros expirados são removidos automaticamente ao verificar ou reenviar.

---

## 📦 Componentes

### Repositório (`TwoFactorRepository`)

| Método | Descrição |
|--------|-----------|
| `save(string $email, string $code, int $expiryMinutes)` | Insere ou atualiza o registro (upsert). |
| `findByEmail(string $email): ?array` | Retorna os dados do registro para o e-mail. |
| `incrementAttempts(string $email)` | Incrementa o contador de tentativas de verificação. |
| `incrementResendCount(string $email, int $maxResend, int $blockMinutes)` | Incrementa reenvios e define bloqueio se limite for atingido. |
| `reset(string $email)` | Remove o registro. |
| `deleteExpired()` | Remove registros expirados (limpeza automática). |
| `isBlockedFromResend(string $email): bool` | Verifica se o reenvio está bloqueado. |
| `getAttempts(string $email): int` | Retorna o número atual de tentativas. |

### Serviço (`TwoFactorService`)

| Método | Descrição |
|--------|-----------|
| `generateAndSend(string $email): array` | Gera código, salva no banco e envia e-mail. |
| `verify(string $email, string $code): array` | Valida o código com controle de tentativas. |
| `resend(string $email): array` | Gera novo código, reenvia e gerencia bloqueio. |
| `getStatus(string $email): array` | Retorna status atual (expiração, tentativas, bloqueio). |
| `reset(string $email)` | Remove o registro manualmente. |
| `cleanup()` | Remove registros expirados. |

### Controlador (`TwoFactorController`)

| Método | Rota | Função |
|--------|------|--------|
| `form(Request $request): string` | `GET /admin/2fa` | Exibe formulário. |
| `verify(Request $request): void` | `POST /admin/2fa/verify` | Valida código. |
| `resend(Request $request): void` | `POST /admin/2fa/resend` | Reenvia código. |

### Middleware (`TwoFactorMiddleware`)

| Responsabilidade | Descrição |
|------------------|-----------|
| Proteger rotas `/admin` | Verifica se `admin_id` e `2fa_verified` estão na sessão. |
| Ignorar rotas públicas | `/admin/login`, `/admin/logout`, `/admin/2fa` (e sub-rotas). |
| Logs | Registra tentativas de acesso sem 2FA. |

---

## ⚙️ Configuração

### Variáveis de ambiente (`.env`)

```env
# ============== CONFIGURAÇÃO SMTP (Outlook) ==============
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=seuemail@outlook.com
MAIL_PASSWORD=xxxx-xxxx-xxxx-xxxx   # Senha de aplicativo
MAIL_ENCRYPTION=tls
MAIL_FROM=seuemail@outlook.com
MAIL_FROM_NAME=Meu Carango
MAIL_TIMEOUT=30

# ============== CONFIGURAÇÃO 2FA ==============
TWO_FACTOR_EXPIRY_MINUTES=5
TWO_FACTOR_MAX_ATTEMPTS=3
TWO_FACTOR_MAX_RESEND=3
TWO_FACTOR_RESEND_BLOCK_MINUTES=30
```

### Configuração do Gmail (alternativa)

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seuemail@gmail.com
MAIL_PASSWORD=senha_app   # Senha de aplicativo
MAIL_ENCRYPTION=tls
MAIL_FROM=seuemail@gmail.com
MAIL_FROM_NAME=Meu Carango
```

---

## 🧪 Como Testar

### Pré‑requisitos

- Servidor rodando.
- Banco de dados com a tabela `two_factor_codes`.
- `.env` configurado com SMTP e variáveis 2FA.
- Acesso ao e-mail do administrador.

### Roteiro de testes

| Cenário | Passos | Resultado esperado |
|---------|--------|---------------------|
| **Fluxo completo** | 1. Acesse `/admin/login`<br>2. Insira e-mail e senha corretos<br>3. Verifique o e-mail com o código<br>4. Insira o código correto em `/admin/2fa` | ✅ Redirecionado para dashboard. |
| **Código incorreto** | 1. Faça login<br>2. Insira código errado (1ª vez) | ❌ Mensagem "Código inválido. Você tem 2 tentativa(s) restante(s)." |
| **Código errado (3 vezes)** | Repita o passo anterior 3 vezes | ❌ Após a 3ª falha, redirecionado para `/admin/login` com mensagem "Número máximo de tentativas excedido." |
| **Código expirado** | 1. Faça login<br>2. Aguarde 5 minutos<br>3. Insira o código | ❌ Mensagem "Código expirado. Faça login novamente." |
| **Reenvio (dentro do limite)** | 1. Faça login<br>2. Clique em "Reenviar código" | ✅ Novo código enviado por e-mail. |
| **Reenvio bloqueado** | 1. Faça login<br>2. Clique em "Reenviar" 3 vezes<br>3. Tente reenviar novamente | ❌ Mensagem "Reenvio bloqueado. Tente novamente em 30 minutos." |
| **Acesso a /admin sem 2FA** | 1. Faça login (senha correta)<br>2. Não complete o 2FA<br>3. Tente acessar `/admin/dashboard` diretamente | ❌ Redirecionado para `/admin/2fa`. |

---

## 📊 Logs

### Níveis e eventos

| Evento | Nível | Contexto |
|--------|-------|----------|
| Código 2FA gerado e enviado | `INFO` | email |
| Falha ao enviar código 2FA | `ERROR` | email, error |
| Verificação bem-sucedida | `INFO` | email |
| Código inválido | `WARNING` | email, attempts_left |
| Excedeu tentativas | `WARNING` | email |
| Reenvio solicitado | `INFO` | email |
| Reenvio bloqueado | `WARNING` | email, blocked_until |
| Acesso negado (2FA pendente) | `WARNING` | uri, admin_id |
| Acesso permitido (2FA ok) | `DEBUG` | uri, admin_id |

---

## 🔧 Solução de Problemas

| Problema | Causa provável | Solução |
|----------|----------------|---------|
| Não recebo o e-mail | Configuração SMTP incorreta | Verifique `.env` (host, porta, senha de aplicativo). Teste com um script simples. |
| Erro "Configuração SMTP incompleta" | Variáveis `MAIL_*` faltando | Verifique se todas estão definidas no `.env`. |
| "Código expirado" | O usuário demorou mais de 5 minutos | Faça login novamente para gerar novo código. |
| "Reenvio bloqueado" | Limite de 3 reenvios foi atingido | Aguarde 30 minutos ou faça login novamente. |
| Tela branca em `/admin/2fa` | Exceção não capturada | Verifique os logs (`storage/logs/app-*.log`). |
| Middleware redirecionando em loop | Rota `/admin/2fa` não está na whitelist | Verifique `TwoFactorMiddleware::shouldIgnore()`. |

---

## 📂 Arquivos Relacionados

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Repository/TwoFactorRepository.php` | Persistência dos códigos 2FA. |
| `app/Service/MailService.php` | Envio de e-mails via SMTP. |
| `app/Service/TwoFactorService.php` | Lógica de negócio 2FA. |
| `app/Controller/TwoFactorController.php` | Controlador HTTP. |
| `app/Middleware/TwoFactorMiddleware.php` | Proteção de rotas. |
| `app/View/admin/2fa.php` | Formulário de verificação. |
| `public/index.php` | Registro de serviços, rotas e middlewares. |
| `.env` | Configurações SMTP e 2FA. |
| `docs/admin-authentication.md` | Documentação principal de autenticação (a ser atualizada). |
| `storage/logs/app-*.log` | Logs de eventos. |

---

## ✅ Critérios de Aceitação (verificação)

- [ ] Tabela `two_factor_codes` criada.
- [ ] `TwoFactorRepository` com todos os métodos.
- [ ] `MailService` funcional (envia e-mails).
- [ ] `TwoFactorService` com regras de negócio completas.
- [ ] `AdminAuthService` inicia fluxo 2FA.
- [ ] `TwoFactorController` com verificação e reenvio.
- [ ] `TwoFactorMiddleware` protegendo `/admin`.
- [ ] Rotas 2FA registradas.
- [ ] View `2fa.php` com feedback e status.
- [ ] `.env` com todas as variáveis.
- [ ] Logs gerados corretamente.
- [ ] Testes manuais validados (após execução).

---

## 📌 Observações Finais

- O 2FA é **obrigatório** para o administrador – não há opção de desativá-lo via `.env` (conforme sua decisão).
- O código é armazenado em banco de dados, permitindo persistência entre requisições.
- O reenvio e as tentativas são controlados para evitar abuso.
- Os logs permitem auditoria completa do fluxo.

---

**Próximo passo:** executar os testes manuais e, se necessário, ajustar a documentação com base nos resultados. 😊
