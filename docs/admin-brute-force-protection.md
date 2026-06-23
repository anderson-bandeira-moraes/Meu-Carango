# Módulo de Limite de Tentativas de Login (Brute Force Protection)

## 📌 Objetivo

Proteger o login do administrador contra ataques de força bruta (brute force), limitando o número de tentativas falhas consecutivas por combinação de **e-mail + IP**. Após exceder o limite, o sistema bloqueia temporariamente novas tentativas, reduzindo o custo de processamento (evita `password_verify` desnecessário) e dificultando ataques automatizados.

---

## 🔄 Fluxo Completo

### Ciclo de vida de uma tentativa de login:

1. **Usuário tenta login** (`POST /admin/login`) → `AdminAuthService::login()`.
2. **Verifica bloqueio expirado**:
   - Busca registro em `login_attempts` para `email + IP`.
   - Se `blocked_until` não for nulo e for menor que `NOW()`, reseta `attempts = 0` e `blocked_until = NULL`.
3. **Verifica bloqueio ativo**:
   - Se `blocked_until > NOW()`, retorna erro **sem** verificar credenciais (reduz custo).
4. **Busca administrador por e-mail** (`AdministradorRepository::findByEmail`).
5. **Valida senha** (`password_verify`):
   - Se **falha**: incrementa contador (`recordFailedAttempt`). Se `attempts >= MAX_LOGIN_ATTEMPTS`, define `blocked_until = NOW() + BLOCK_DURATION_MINUTES`.
   - Se **sucesso**: reseta tentativas (`resetAttempts`) e limpa registros antigos (`deleteOldRecords`).

---

## 🗄️ Estrutura de Dados

### Tabela: `login_attempts`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | int unsigned | Chave primária. |
| `email` | varchar(100) | E-mail do administrador. |
| `ip_address` | varchar(45) | Endereço IP do cliente. |
| `attempts` | tinyint unsigned | Número de tentativas falhas consecutivas. |
| `last_attempt_at` | datetime | Data/hora da última tentativa. |
| `blocked_until` | datetime | Data/hora até quando o bloqueio está ativo (NULL se não bloqueado). |
| `created_at` | datetime | Data/hora de criação do registro. |
| `updated_at` | datetime | Data/hora da última atualização. |

**Índices:**
- `PRIMARY KEY (id)`
- `UNIQUE KEY email_ip (email, ip_address)` – evita duplicatas.
- `KEY blocked_until (blocked_until)` – otimiza consultas de bloqueio.

---

## 🧩 Componentes do Módulo

### 1. Repositório (`LoginAttemptRepository`)

**Arquivo:** `app/Repository/LoginAttemptRepository.php`

**Responsabilidade:** Persistência e consultas à tabela `login_attempts`.

| Método | Descrição |
|--------|-----------|
| `recordFailedAttempt(string $email, string $ip): void` | Incrementa `attempts` ou cria novo registro. Se `attempts >= MAX_LOGIN_ATTEMPTS`, define `blocked_until`. |
| `isBlocked(string $email, string $ip): bool` | Retorna `true` se `blocked_until > NOW()`. |
| `resetAttempts(string $email, string $ip): void` | Define `attempts = 0` e `blocked_until = NULL`. |
| `deleteOldRecords(int $days = 30): void` | Remove registros com `created_at` ou `updated_at` mais antigos que X dias. |
| `getAttempts(string $email, string $ip): ?array` | Retorna dados do registro (para depuração). |
| `updateLastAttempt(int $id): void` | Atualiza `last_attempt_at = NOW()` (usado quando já bloqueado). |

**Configurações lidas do `.env`:**
- `MAX_LOGIN_ATTEMPTS` – número máximo de tentativas antes do bloqueio.
- `BLOCK_DURATION_MINUTES` – duração do bloqueio.
- `LOGIN_ATTEMPTS_RETENTION_DAYS` – dias de retenção de registros.

**Logs:**
- `INFO` – reset do contador, limpeza de registros.
- `WARNING` – bloqueio ativado.
- `DEBUG` – primeira tentativa, tentativa durante bloqueio.
- `ERROR` – falha na query.

---

### 2. Integração com `AdminAuthService`

**Arquivo:** `app/Service/AdminAuthService.php`

**Modificações no método `login()`:**

| Etapa | Código | Descrição |
|-------|--------|-----------|
| **1. Verificar bloqueio expirado** | `$attemptData = $this->attemptRepository->getAttempts($email, $clientIp);`<br>`if ($attemptData && $attemptData['blocked_until'] < NOW()) { $this->attemptRepository->resetAttempts($email, $clientIp); }` | Se o bloqueio expirou, reseta o contador. |
| **2. Verificar bloqueio ativo** | `if ($this->attemptRepository->isBlocked($email, $clientIp)) { return erro; }` | Se bloqueado, retorna erro **sem** processar senha. |
| **3. Registrar falha** | `$this->attemptRepository->recordFailedAttempt($email, $clientIp);` | Após credenciais inválidas, incrementa tentativas. |
| **4. Resetar tentativas em caso de sucesso** | `$this->attemptRepository->resetAttempts($email, $clientIp);`<br>`$this->attemptRepository->deleteOldRecords();` | Após login bem-sucedido, reseta contador e limpa registros antigos. |

**Dependências:**
- `LoginAttemptRepository` (injetado no construtor).

---

### 3. Registro no Container (`index.php`)

**Arquivo:** `public/index.php`

**Registros:**

```php
$container->set(App\Repository\LoginAttemptRepository::class, function($c) {
    return new App\Repository\LoginAttemptRepository(
        $c->get(PDO::class),
        $c->get(\Monolog\Logger::class)
    );
});

// No AdminAuthService:
$container->set(App\Service\AdminAuthService::class, function($c) {
    return new App\Service\AdminAuthService(
        $c->get(App\Repository\AdministradorRepository::class),
        $c->get(SessionInterface::class),
        $c->get(\Monolog\Logger::class),
        $c->get(App\Repository\LoginAttemptRepository::class), // <-- dependência
        $c->get(App\Service\TwoFactorService::class)
    );
});
```

---

## ⚙️ Configuração

### Variáveis de ambiente (`.env`)

```env
# ============== LIMITE DE TENTATIVAS DE LOGIN ==============
MAX_LOGIN_ATTEMPTS=5
BLOCK_DURATION_MINUTES=60
LOGIN_ATTEMPTS_RETENTION_DAYS=30
```

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `MAX_LOGIN_ATTEMPTS` | Número máximo de tentativas falhas antes do bloqueio. | `5` |
| `BLOCK_DURATION_MINUTES` | Duração do bloqueio (em minutos). | `60` |
| `LOGIN_ATTEMPTS_RETENTION_DAYS` | Dias de retenção de registros (limpeza automática). | `30` |

---

## 📊 Logs e Monitoramento

| Evento | Nível | Contexto |
|--------|-------|----------|
| Primeira tentativa falha | `DEBUG` | email, ip |
| Bloqueio ativado | `WARNING` | email, ip, attempts, blocked_until, max_attempts |
| Tentativa durante bloqueio | `DEBUG` | email, ip, blocked_until |
| Reset após login bem-sucedido | `DEBUG` / `INFO` | email, ip |
| Reset após expiração do bloqueio | `INFO` | email, ip, previous_attempts |
| Limpeza de registros antigos | `INFO` / `DEBUG` | deleted, days |
| Tentativa de login durante bloqueio (AdminAuthService) | `WARNING` | email, ip |

**Exemplos de logs:**

```json
[2026-06-23 18:45:16] app.WARNING: Tentativa de login durante bloqueio ativo {"email":"admin@meucarango.com","ip":"127.0.0.1"}
[2026-06-23 18:45:16] app.WARNING: Tentativa de login durante bloqueio de reenvio 2FA {"email":"admin@meucarango.com","blocked_until":"2026-06-23 19:14:38","minutes_left":30}
[2026-06-23 18:45:49] app.INFO: Contador de reenvios resetado para 0 (bloqueio expirado) {"email":"admin@meucarango.com"}
```

---

## 🧪 Testes Validados

| Cenário | Resultado Esperado |
|---------|---------------------|
| **Bloqueio após 5 tentativas falhas** | ❌ Mensagem: "Muitas tentativas de login. Tente novamente mais tarde." |
| **Tentativa durante bloqueio** | ❌ Mesma mensagem (sem processar senha). |
| **Reset após login bem-sucedido** | ✅ Contador zerado, bloqueio removido. |
| **Reset após expiração do bloqueio** | ✅ Contador zerado automaticamente ao expirar. |
| **Limpeza de registros antigos** | ✅ Registros com mais de 30 dias removidos. |
| **Logs gerados** | ✅ `DEBUG`, `WARNING`, `INFO`, `ERROR` conforme esperado. |

---

## 📂 Arquivos Relacionados

### Diretos (módulo de tentativas de login)

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Repository/LoginAttemptRepository.php` | Persistência e consultas. |
| `app/Service/AdminAuthService.php` | Integração com o login (verificação e registro). |

### Indiretos (dependências e infraestrutura)

| Arquivo | Papel |
|---------|-------|
| `public/index.php` | Registro do repositório no container. |
| `.env` | Configurações (`MAX_LOGIN_ATTEMPTS`, `BLOCK_DURATION_MINUTES`, etc.). |
| `app/Core/Request.php` | Obtenção do IP do cliente (`getClientIp()`). |
| `app/Controller/AdminAuthController.php` | Captura e repasse do IP para o service. |
| `storage/logs/app-*.log` | Registro de logs. |

---

## 📌 Observações Finais

- **Custo reduzido:** a verificação de bloqueio é feita antes de `password_verify`, que é computacionalmente mais caro.
- **Persistência:** o bloqueio é armazenado no banco, persistindo entre requisições e reinicializações.
- **Limpeza automática:** registros antigos são removidos a cada login bem-sucedido, mantendo a tabela leve.
- **Configurável:** todos os parâmetros (limite, duração, retenção) são ajustáveis via `.env`.
- **Logs completos:** todas as ações são registradas para auditoria e depuração.

---