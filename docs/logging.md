# Sistema de Logs com Monolog

## 📌 Objetivo

O sistema de logs tem como objetivo **registrar eventos, erros e ações de segurança** da aplicação para fins de:

- **Monitoramento:** detectar problemas em tempo real.
- **Depuração:** facilitar a identificação e correção de bugs.
- **Auditoria:** rastrear ações administrativas e tentativas de acesso.
- **Segurança:** identificar padrões de ataque (ex: CSRF, força bruta).

---

## 🧩 Biblioteca Utilizada

- **Monolog** – biblioteca de logging mais popular para PHP.
- **Versão:** `^3.0` (instalada via Composer).
- **Motivo da escolha:** flexibilidade, suporte a múltiplos handlers, níveis de log, processadores e integração com PSR-3.

---

## ⚙️ Configuração

### Arquivo de configuração

Localizado em `config/logging.php`:

```php
<?php

use Monolog\Level;
use Monolog\Processor\WebProcessor;

$env = $_ENV['APP_ENV'] ?? 'production';
$levelMap = [
    'development' => Level::Debug,
    'testing'     => Level::Debug,
    'production'  => Level::Warning,
];
$logLevel = $levelMap[$env] ?? Level::Warning;

return [
    'channel'    => 'app',
    'path'       => ROOT_DIR . '/storage/logs/app.log',
    'days'       => 30,                   // retenção de arquivos
    'level'      => $logLevel,            // DEBUG em dev, WARNING em prod
    'processors' => [
        WebProcessor::class,              // adiciona IP, URI, método, user agent
    ],
];
```

### Ambiente

- **Desenvolvimento (`APP_ENV=development`)**: nível `DEBUG` – registra todos os eventos (inclusive informativos).
- **Produção (`APP_ENV=production`)**: nível `WARNING` – registra apenas avisos e erros (evita poluição).

---

## 📂 Localização dos Logs

Os logs são armazenados em:

```
storage/logs/app-YYYY-MM-DD.log
```

A rotação é diária e os arquivos são mantidos por **30 dias** (configurável via `'days'`).

---

## 📊 Níveis de Log Utilizados

| Nível | Uso |
|-------|-----|
| `DEBUG` | Detalhes extensos para depuração (apenas em desenvolvimento). |
| `INFO` | Eventos significativos (ex: login bem-sucedido, logout). |
| `WARNING` | Ocorrências anormais, mas não críticas (ex: CSRF inválido, 404, 403). |
| `ERROR` | Erros que afetam a execução, mas não derrubam o sistema (ex: exceção 500). |
| `CRITICAL` | (Reservado para falhas graves, como indisponibilidade do banco). |

---

## 🔍 O que Está Sendo Logado

### 1. Erros HTTP (no `index.php`)

| Código | Nível | Contexto |
|--------|-------|----------|
| **404** | `WARNING` | URI, método HTTP, mensagem da exceção. |
| **403** | `WARNING` | URI, método HTTP, mensagem da exceção. |
| **500** | `ERROR` | Arquivo, linha, stack trace, URI, método HTTP. |

**Exemplo de log 404:**
```
[2026-06-18 14:30:25] app.WARNING: Página não encontrada {"uri":"/admin/xyz","method":"GET","message":"Nenhuma rota encontrada para GET /admin/xyz"} []
```

**Exemplo de log 500:**
```
[2026-06-18 15:10:02] app.ERROR: Uncaught Error: Class "App\Core\NaoExiste" not found {"file":"/opt/lampp/htdocs/app/Controller/AdminAuthController.php","line":45,"trace":"#0 ...","uri":"/admin/login","method":"POST"} []
```

---

### 2. CSRF Inválido (no `CsrfValidationMiddleware`)

| Nível | Contexto |
|-------|----------|
| `WARNING` | IP, URI, método HTTP. |

**Exemplo:**
```
[2026-06-18 14:35:10] app.WARNING: CSRF token inválido {"ip":"192.168.1.100","uri":"/admin/login","method":"POST"} []
```

---

### 3. Autenticação Administrativa (no `AdminAuthService`)

| Evento | Nível | Contexto |
|--------|-------|----------|
| **Login bem-sucedido** | `INFO` | Email, IP |
| **Falha de login** | `WARNING` | Email, IP, motivo |
| **Logout** | `INFO` | Email, IP |

**Exemplos:**

```json
[2026-06-18 14:40:15] app.INFO: Login de administrador bem-sucedido {"email":"admin@meucarango.com","ip":"192.168.1.100"} []
```

```json
[2026-06-18 14:41:02] app.WARNING: Tentativa de login falhou (admin) {"email":"invasor@teste.com","ip":"10.0.0.5","motivo":"Credenciais inválidas"} []
```

```json
[2026-06-18 15:00:00] app.INFO: Logout de administrador {"email":"admin@meucarango.com","ip":"192.168.1.100"} []
```

---

## 🧪 Como Testar os Logs

### Forçar um erro 404
- Acesse uma URL inexistente (ex: `/pagina-que-nao-existe`).
- Verifique o arquivo de log: deve conter uma linha com `app.WARNING: Página não encontrada`.

### Forçar um erro CSRF
1. Abra duas abas com `/admin/login`.
2. Faça login na primeira aba.
3. Na segunda aba, sem recarregar, tente fazer login novamente.
4. Verifique o log: deve conter `app.WARNING: CSRF token inválido` e `app.WARNING: Acesso negado (CSRF ou permissão)`.

### Forçar um erro 500
- Remova temporariamente uma dependência (ex: comente uma linha no controller).
- Acesse a rota afetada.
- Verifique o log: deve conter `app.ERROR` com stack trace.

### Testar logs de autenticação
- Faça login com credenciais válidas e inválidas.
- Faça logout.
- Verifique se os logs `INFO` e `WARNING` aparecem.

---

## 🔧 Como Adicionar Novos Logs em Serviços

### 1. Injetar o Logger no serviço

```php
use Monolog\Logger;

class MeuService
{
    public function __construct(private Logger $logger) {}

    public function fazerAlgo(): void
    {
        // ...
        $this->logger->info('Ação executada', ['id' => 123]);
    }
}
```

### 2. Registrar no container (`index.php`)

```php
$container->set(MeuService::class, function($c) {
    return new MeuService(
        $c->get(\Monolog\Logger::class)
    );
});
```

### 3. Usar em controllers ou outros serviços

```php
$this->meuService->fazerAlgo();
```

---

## 📈 Monitoramento e Boas Práticas

### Em desenvolvimento
- Mantenha `APP_ENV=development` para ver todos os logs (`DEBUG`).
- Use `tail -f storage/logs/app-$(date +%Y-%m-%d).log` para acompanhar em tempo real.

### Em produção
- Mantenha `APP_ENV=production` para evitar logs excessivos.
- Configure um sistema de alertas para níveis `ERROR` ou superiores (ex: enviar e-mail, Slack, etc.).
- Monitore o espaço em disco: os logs são rotacionados e retidos por 30 dias, mas é importante verificar periodicamente.

### Segurança
- A pasta `storage/logs/` está fora do document root (não acessível via navegador).
- Nunca registre dados sensíveis como senhas, tokens de API ou informações pessoais em logs.
- Em caso de dúvida, use `$logger->debug()` apenas em desenvolvimento.

---

## 📂 Arquivos Relacionados

| Arquivo | Responsabilidade |
|---------|------------------|
| `config/logging.php` | Configuração centralizada do Monolog. |
| `public/index.php` | Registro do Logger no container e logs de erro HTTP. |
| `app/Middleware/CsrfValidationMiddleware.php` | Log de CSRF inválido. |
| `app/Service/AdminAuthService.php` | Logs de autenticação administrativa. |
| `app/Controller/AdminAuthController.php` | Captura IP e repassa para o service. |
| `storage/logs/` | Diretório onde os logs são armazenados. |

---

## ✅ Resumo

O sistema de logs com Monolog oferece:

- Registro estruturado de erros HTTP, CSRF e autenticação.
- Rotação automática e retenção de 30 dias.
- Níveis de log ajustáveis por ambiente (`DEBUG` em dev, `WARNING` em prod).
- Contexto enriquecido com IP, URI, método e stack trace.
- Fácil extensibilidade para novos serviços.

Com essa base, você tem uma camada de logging profissional, pronta para monitoramento e auditoria em produção. 🚀