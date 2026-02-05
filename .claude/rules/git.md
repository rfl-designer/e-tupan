# Git

- Siga as convenções de commit e branch estabelecidas no projeto.
- Sempre verifique o status antes de fazer commit.

## Commits

### Mensagem de Commit

Use o formato: `tipo: descrição curta`

| Tipo | Uso |
|------|-----|
| `feat` | Nova funcionalidade |
| `fix` | Correção de bug |
| `refactor` | Refatoração sem mudança de comportamento |
| `style` | Formatação, espaços, vírgulas |
| `docs` | Documentação |
| `test` | Adição/correção de testes |
| `chore` | Tarefas de manutenção |

### Boas Práticas

- Commits pequenos e focados em uma única mudança
- Mensagens no imperativo: "add feature" não "added feature"
- Primeira linha com no máximo 72 caracteres
- Não commitar arquivos sensíveis (.env, credentials, keys)

### Arquivos que NÃO devem ser commitados

- `.env` (use `.env.example`)
- `auth.json` (credenciais Composer)
- `storage/logs/*`
- `node_modules/`
- `vendor/`
- Arquivos com secrets, tokens ou senhas

## Branches

### Nomenclatura

| Prefixo | Uso |
|---------|-----|
| `feature/` | Nova funcionalidade |
| `fix/` | Correção de bug |
| `hotfix/` | Correção urgente em produção |
| `refactor/` | Refatoração |
| `release/` | Preparação de release |

### Exemplo

```
feature/user-authentication
fix/login-validation-error
hotfix/payment-gateway-timeout
```

## Workflow

### Antes de Commitar

1. `git status` - verificar arquivos modificados
2. `git diff` - revisar mudanças
3. `vendor/bin/pint --dirty` - formatar código
4. `php artisan test --filter=...` - rodar testes relevantes

### Comandos Úteis

```bash
# Ver histórico resumido
git log --oneline -10

# Ver mudanças staged
git diff --staged

# Adicionar arquivos específicos
git add app/Models/User.php

# Commit com mensagem
git commit -m "feat: add user authentication"
```

## Common Pitfalls

- Commitar `.env` ou arquivos com credenciais
- Commits muito grandes com múltiplas mudanças não relacionadas
- Mensagens vagas como "fix bug" ou "update"
- Não rodar testes antes de commitar
- Esquecer de formatar com Pint antes do commit
