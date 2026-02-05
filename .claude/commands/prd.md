---
description: Cria o arquivo PRD.md com requisitos e features do projeto
argument-hint: [nome-do-projeto]
allowed-tools:
  - Read
  - Write
  - Glob
  - AskUserQuestion
---

# Criar PRD (Product Requirements Document)

## Contexto

Você está criando o PRD (`dev/PRD.md`) baseado na especificação do projeto. O PRD contém a lista de features que serão desenvolvidas.

## Instruções

1. **Verificar SPEC.md**: Leia o arquivo `dev/SPEC.md` para entender o projeto.
   - Se não existir, informe ao usuário que precisa executar `/laraflow:spec` primeiro.

2. **Analisar especificações**: Extraia as funcionalidades principais do SPEC.md.

3. **Definir features**: Quebre as funcionalidades em features desenvolvíveis. Cada feature deve ser:
   - Independente (pode ser desenvolvida separadamente)
   - Testável (tem critérios claros de conclusão)
   - Estimável (escopo bem definido)

4. **Priorizar**: Organize as features por prioridade:
   - **Alta**: Essenciais para MVP
   - **Média**: Importantes mas não bloqueantes
   - **Baixa**: Nice-to-have

5. **Usar template**: Leia o template em `skills/laravel-workflow/PRD-TEMPLATE.md` (se existir).

6. **Gerar PRD.md**: Crie o arquivo `dev/PRD.md`.

## Estrutura do PRD.md

```markdown
# PRD - [Nome do Projeto]

## Visão Geral
[Resumo do projeto baseado no SPEC.md]

## Objetivos do Produto
1. [Objetivo 1]
2. [Objetivo 2]

## Features

### Feature: [Nome da Feature]
- **ID**: feature-01
- **Status**: `todo`
- **Prioridade**: Alta | Média | Baixa
- **Descrição**: [Descrição detalhada]
- **Dependências**: [Features que precisam ser concluídas antes]

### Feature: [Nome da Feature 2]
- **ID**: feature-02
- **Status**: `todo`
- **Prioridade**: Alta | Média | Baixa
- **Descrição**: [Descrição detalhada]
- **Dependências**: []

...

## Métricas de Sucesso
- [Métrica 1]
- [Métrica 2]

## Cronograma Sugerido
| Fase | Features |
|------|----------|
| MVP | feature-01, feature-02 |
| V1.1 | feature-03 |
...
```

## Status das Features

- `todo`: Ainda não iniciada
- `in-progress`: Em desenvolvimento
- `done`: Concluída

## Após Criação

Informe ao usuário que:
1. O PRD foi criado com sucesso
2. Para cada feature, execute `/laraflow:create-feature <nome-feature>` para quebrar em user stories
3. Liste as features criadas para facilitar
