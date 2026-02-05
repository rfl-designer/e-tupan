---
name: laravel-workflow
description: |
  Gerencia fluxo de desenvolvimento Laravel com specs, PRDs e user stories.
  Use para: criar documentação de projeto, quebrar features em stories,
  seguir workflow de desenvolvimento, entender estrutura de features.
  Keywords: spec, prd, feature, user story, desenvolvimento, workflow, laraflow
allowed-tools:
  - Read
  - Write
  - Edit
  - Bash
  - Glob
  - Grep
---

# Laravel Workflow Skill

Este skill gerencia o fluxo de desenvolvimento orientado a especificações para projetos Laravel.

## Visão Geral

O Laraflow é um workflow estruturado para desenvolvimento Laravel que segue estas etapas:

1. **Especificação** (`dev/SPEC.md`) - Define o escopo e objetivos do projeto
2. **PRD** (`dev/PRD.md`) - Lista as features com prioridades e status
3. **Features** (`dev/features-plan/*.json`) - Quebra features em user stories
4. **Desenvolvimento** - Implementa cada user story com TDD

## Estrutura de Arquivos

```
dev/
├── SPEC.md                    # Especificação do projeto
├── PRD.md                     # Product Requirements Document
└── features-plan/
    ├── user-auth.json         # User stories da feature auth
    ├── product-catalog.json   # User stories do catálogo
    └── ...
```

## Templates Disponíveis

- [SPEC-TEMPLATE.md](SPEC-TEMPLATE.md) - Template para especificações
- [PRD-TEMPLATE.md](PRD-TEMPLATE.md) - Template para PRD
- [FEATURE-TEMPLATE.json](FEATURE-TEMPLATE.json) - Schema JSON para features

## Comandos Disponíveis

| Comando | Descrição |
|---------|-----------|
| `/spec` | Cria especificação do projeto |
| `/prd` | Cria PRD com lista de features |
| `/create-feature <nome>` | Cria feature com user stories |
| `/dev-story <feature> <story>` | Desenvolve user story completa |

## Fluxo de Trabalho

```
/laraflow:spec
     ↓
/laraflow:prd
     ↓
/laraflow:create-feature auth
     ↓
/laraflow:dev-story auth us-01
     ↓
[Testes] → [Código] → [Pint] → [PHPStan] → [Commit] → [PR]
```

## Status

### Features (PRD.md)
- `todo` - Não iniciada
- `in-progress` - Em desenvolvimento
- `done` - Concluída

### User Stories (JSON)
- `todo` - Não iniciada
- `in-progress` - Em desenvolvimento
- `done` - Concluída e testada

## Convenções

1. **Nomes de features**: Use kebab-case (`user-auth`, `product-catalog`)
2. **Nomes de stories**: Use prefixo `us-` seguido de número (`us-01`, `us-02`)
3. **Critérios de aceite**: Devem ser testáveis e específicos
4. **Commits**: Siga conventional commits (`feat:`, `fix:`, `refactor:`)
