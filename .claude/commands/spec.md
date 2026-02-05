---
description: Cria o arquivo SPEC.md com especificações do projeto
argument-hint: [nome-do-projeto]
allowed-tools:
  - Read
  - Write
  - Bash(mkdir:*)
  - Glob
  - AskUserQuestion
---

# Criar Especificação do Projeto

## Contexto

Você está criando o arquivo de especificação (`dev/SPEC.md`) para um projeto Laravel. Este arquivo serve como base para todo o desenvolvimento.

## Instruções

1. **Verificar/Criar estrutura**: Execute `mkdir -p dev/features-plan` para garantir que a estrutura existe.

2. **Nome do projeto**:
   - Se foi passado como argumento (`$ARGUMENTS`), use-o como nome do projeto
   - Se não foi passado, pergunte ao usuário qual é o nome do projeto

3. **Coletar informações**: Pergunte ao usuário sobre:
   - Descrição breve do projeto
   - Objetivo principal
   - Público-alvo
   - Principais funcionalidades esperadas
   - Integrações necessárias (se houver)
   - Requisitos técnicos específicos

4. **Usar template**: Leia o template em `skills/laravel-workflow/SPEC-TEMPLATE.md` (se existir) para estruturar o documento.

5. **Gerar SPEC.md**: Crie o arquivo `dev/SPEC.md` com as informações coletadas.

## Estrutura Esperada do SPEC.md

```markdown
# Especificação - [Nome do Projeto]

## Visão Geral
[Descrição do projeto]

## Objetivo
[Objetivo principal]

## Público-Alvo
[Quem usará o sistema]

## Funcionalidades Principais
- [Funcionalidade 1]
- [Funcionalidade 2]
...

## Integrações
- [Integração 1]
...

## Requisitos Técnicos
- Laravel 12
- PHP 8.4
- [Outros requisitos]

## Escopo
### Incluído
- [Item 1]

### Não Incluído
- [Item 1]
```

## Após Criação

Informe ao usuário que o próximo passo é executar `/laraflow:prd` para criar o PRD com as features do projeto.
