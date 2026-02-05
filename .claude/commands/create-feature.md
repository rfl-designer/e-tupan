---
description: Quebra uma feature em user stories no formato JSON
argument-hint: <nome-da-feature>
allowed-tools:
  - Read
  - Write
  - Bash(mkdir:*)
  - Glob
  - AskUserQuestion
---

# Criar Feature com User Stories

## Contexto

Você está criando o arquivo JSON de uma feature (`dev/features-plan/<nome-feature>.json`) com suas user stories.

## Instruções

1. **Validar argumento**: O nome da feature (`$ARGUMENTS`) é obrigatório.
   - Se não foi passado, peça ao usuário para informar.
   - Use kebab-case para o nome (ex: `user-auth`, `product-catalog`).

2. **Verificar PRD.md**: Leia o arquivo `dev/PRD.md` para encontrar a feature.
   - Se não existir, informe que precisa executar `/laraflow:prd` primeiro.
   - Se a feature não existir no PRD, pergunte se deseja criá-la mesmo assim.

3. **Criar estrutura**: Execute `mkdir -p dev/features-plan`.

4. **Definir user stories**: Para cada funcionalidade da feature, crie uma user story com:
   - Nome único (us-01, us-02, etc.)
   - Descrição no formato: "Como [usuário], quero [ação] para [benefício]"
   - Critérios de aceite específicos e testáveis

5. **Usar template**: Leia `skills/laravel-workflow/FEATURE-TEMPLATE.json` para referência.

6. **Gerar arquivo JSON**: Crie `dev/features-plan/<nome-feature>.json`.

## Estrutura do JSON

```json
{
  "feature": "nome-da-feature",
  "description": "Descrição da feature do PRD",
  "prd_id": "feature-01",
  "user_stories": [
    {
      "name": "us-01",
      "description": "Como usuário, quero [ação] para [benefício]",
      "status": "todo",
      "acceptance_criteria": [
        "Quando [condição], então [resultado esperado]",
        "Deve [comportamento esperado]",
        "Não deve [comportamento não esperado]"
      ]
    },
    {
      "name": "us-02",
      "description": "Como administrador, quero [ação] para [benefício]",
      "status": "todo",
      "acceptance_criteria": [
        "Critério 1",
        "Critério 2"
      ]
    }
  ]
}
```

## Status das User Stories

- `todo`: Ainda não iniciada
- `in-progress`: Em desenvolvimento
- `done`: Concluída e testada

## Boas Práticas para Critérios de Aceite

1. **Seja específico**: "O formulário valida email" → "O formulário exibe erro 'Email inválido' quando formato incorreto"
2. **Seja testável**: Cada critério deve poder virar um teste automatizado
3. **Cubra edge cases**: Inclua cenários de erro e limites
4. **Use formato Given-When-Then quando apropriado**

## Após Criação

Informe ao usuário:
1. O arquivo foi criado em `dev/features-plan/<nome-feature>.json`
2. Liste as user stories criadas
3. Para desenvolver cada story, execute `/laraflow:dev-story <feature> <story>`
   - Exemplo: `/laraflow:dev-story user-auth us-01`
