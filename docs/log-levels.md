# Níveis de Log – Referência Rápida

Baseado na **PSR‑3 Logger Interface** (PHP-FIG) e na implementação do **Monolog**.

---

## 📊 Tabela de Níveis de Log

| Nível | Uso Recomendado | Quando Usar | Exemplo no Sistema |
|-------|-----------------|-------------|---------------------|
| `DEBUG` | Desenvolvimento / Depuração | Informações detalhadas para entender o fluxo interno. | (Não usado em produção) – Ex: `"SQL executada: SELECT * FROM usuarios"` |
| `INFO` | Eventos significativos e esperados | Ações normais que devem ser registradas para auditoria ou rastreamento. | `"Login de administrador bem‑sucedido"`<br>`"Logout de administrador"` |
| `NOTICE` | Eventos normais, mas que merecem atenção | Comportamentos incomuns que não são erros. | (Não usado ainda) – Ex: `"Parâmetro X não informado, usando valor padrão"` |
| `WARNING` | Ocorrências anormais que não quebram o sistema | Indica que algo pode estar errado e merece investigação. | `"CSRF token inválido"`<br>`"Página não encontrada (404)"`<br>`"Tentativa de login com credenciais inválidas"` |
| `ERROR` | Erros que afetam a execução da requisição | Falhas que impedem a conclusão de uma operação, mas não derrubam o sistema. | `"Exceção não capturada (500)"`<br>`"Falha ao enviar e‑mail"` |
| `CRITICAL` | Erros graves que exigem ação imediata | Componentes críticos indisponíveis. | `"Banco de dados inacessível"`<br>`"Falha na conexão com API externa"` |
| `ALERT` | Ação imediata necessária | Situações de emergência que requerem intervenção urgente. | (Reservado para ataques ou falhas críticas) |
| `EMERGENCY` | Sistema inutilizável | O sistema não pode funcionar. | (Raramente usado) – Ex: `"Falha no bootstrap da aplicação"` |

---

## 📚 Documentação de Referência

- **PSR‑3: Logger Interface** – Padrão PHP-FIG para interfaces de logging.  
  [https://www.php-fig.org/psr/psr-3/](https://www.php-fig.org/psr/psr-3/)

- **Monolog** – Biblioteca de logging utilizada no projeto.  
  [https://github.com/Seldaek/monolog](https://github.com/Seldaek/monolog)

---

## ✅ Como Escolher o Nível

1. **Fluxo normal e esperado** → `INFO`
2. **Comportamento anormal, mas não prejudicial** → `WARNING`
3. **Falha em uma requisição** → `ERROR`
4. **Falha que afeta todo o sistema** → `CRITICAL`
5. **Depuração detalhada (apenas desenvolvimento)** → `DEBUG`