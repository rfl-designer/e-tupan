# Workflow de Desenvolvimento

Processo completo para implementar user stories no projeto Laravel.

## 1. Carregar User Story

```
Arquivo: dev/features-plan/$1.json
```

**Ações:**

- Leia o JSON da Feature
- Encontre a user story pelo name (`$2`)
- Se não encontrar, liste as disponíveis e peça para escolher
- Verifique o status e as dependências
    - Dependências devem ter `"status": "done"`
- **Não desenvolver** se status já é `done`

**Critério de Saída:** User story carregada e validada

---

## 2. Analisar Code Base

Analise o código existente:

- Estrutura de Controllers, Models, Views
- Padrões de código utilizados no projeto
- Components Livewire/Flux disponíveis
- Services e Actions existentes
- Migrations e estrutura do banco

**Critério de Saída:** Contexto do projeto compreendido

---

## 3. Criar Plano de Implementação

Use `TodoWriter` para criar tarefas baseadas nos critérios de aceite.

**SEMPRE** retorne ao usuário:

```
**User Story**

[descrição da user story]

**Critérios de Aceite**

1. [critério 1]
2. [critério 2]
3. [critério 3]
...

**Plano de Implementação**

- [ ] task 01
- [ ] task 02
- [ ] task 03
...
```

**Critério de Saída:** Plano aprovado pelo usuário

---

## 4. Implementar Backend (Laravel/PHP)

**Guidelines:**

- [laravel.md](guidelines/laravel.md)
- [php.md](guidelines/php.md)

**Ações:**

- Seguir ordem do Plano de Implementação
- Criar/atualizar Models com relationships
- Criar/atualizar Migrations
- Implementar Controllers ou Actions
- Criar Form Requests para validação
- Implementar Services quando necessário
- Código limpo, legível e manutenível
- Tratar erros e edge cases

**Padrões:**

- Use Actions para lógica de negócio complexa
- Use Form Requests para validação
- Use Resources para transformação de dados em APIs
- Use Events/Listeners para side effects

**Critério de Saída:** Backend implementado e funcional

---

## 5. Implementar Frontend (Livewire/Blade)

**Guidelines:**

- [livewire.md](guidelines/livewire.md)
- [tailwindcss.md](guidelines/tailwindcss.md)
- [flux-ui.md](guidelines/flux-ui.md)

**Ações:**

- Criar componentes Livewire v3
- Usar Flux UI para componentes de interface
- AlpineJS apenas para estados locais do DOM
- Implementar Pattern Actions nos componentes
- Seguir padrões de acessibilidade

**Padrões:**

- Componentes Livewire para interatividade server-side
- Flux UI para forms, modals, dropdowns, etc.
- AlpineJS para toggles, tabs e estados efêmeros
- Tailwind CSS v4 para estilização

**Critério de Saída:** Frontend implementado e funcional

---

## 6. Qualidade de Código

Execute as ferramentas de qualidade:

```bash
# Formatar código com Pint
vendor/bin/pint --dirty

# Análise estática com PHPStan (nível 6)
vendor/bin/phpstan analyse --level=6 --memory-limit=512M
```

**Ações:**

- Corrigir todos os erros do PHPStan
- Garantir formatação consistente
- Resolver warnings relevantes

**Critério de Saída:** Zero erros no PHPStan, código formatado

---

## 7. Code Simplifier

Invoque o agent `laravel-simplifier` para revisar e simplificar o código.

Use a ferramenta `Task` para delegar a revisão.

**Foco da simplificação:**

- Reduzir complexidade ciclomática
- Extrair métodos longos
- Aplicar princípios SOLID
- Remover código duplicado

**Critério de Saída:** Código revisado e simplificado

---

## 8. Escrever Testes

**Guideline:** [pest.md](guidelines/pest.md)

Após implementação completa, escreva testes para:

- Cobrir **todos** os critérios de aceite
- Testar edge cases identificados
- Testar validações e erros
- Garantir que funcionalidades existentes não quebraram

```bash
# Executar testes da feature
php artisan test --filter=NomeDaFeature

# Executar todos os testes
php artisan test
```

**Tipos de teste:**

- Feature tests para fluxos completos
- Unit tests para lógica isolada
- Livewire tests para componentes

**Critério de Saída:** Todos os testes passando

---

## 9. Preparar Commit

**IMPORTANTE:** Antes de fazer commit, PERGUNTE ao usuário se pode prosseguir.

Mostre:

- Arquivos modificados/criados
- Resumo das mudanças
- Mensagem de commit sugerida

### Se aprovado, execute:

```bash
# Adicionar arquivos
git add .

# Commit com mensagem descritiva
git commit -m "feat($1): implementa $2

- [Descrição das mudanças principais]
- [Arquivos criados/modificados]"

# Push
git push origin HEAD
```

---

## 10. Criar Pull Request

Use o template em [templates/pr-template.md](templates/pr-template.md).

```bash
gh pr create \
  --title "feat($1): implementa $2" \
  --body-file templates/pr-template.md
```

Ou crie manualmente preenchendo o template.

**Critério de Saída:** PR criada e link disponível

---

## 11. Atualizar Status

Edite o arquivo `dev/features-plan/$1.json`:

- Localize a user story pelo name
- Mude o status para `"done"`
- Salve o arquivo

```json
{
  "name": "$2",
  "status": "done",
  ...
}
```

---

## Resumo Final

**SEMPRE** informe ao usuário:

1. ✅ User story concluída
2. 🔗 Link da PR criada
3. 📋 Próximas user stories pendentes (se houver)
4. ⚠️ Observações ou débitos técnicos identificados
