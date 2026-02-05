---
name: laravel-developer
description: >-
  Desenvolve user stories em PHP 8.4+, Laravel 12, Livewire v3, AlpineJS, 
  Tailwind CSS v4 e Flux UI. Use quando precisar implementar features, 
  criar componentes Livewire, ou desenvolver funcionalidades no projeto Laravel.
allowed-tools:
  - Read
  - Write
  - Edit
  - Bash
  - Glob
  - Grep
  - Task
  - TodoWriter
---

# Laravel Developer

Desenvolve user stories seguindo stack Laravel 12 + Livewire v3 + Flux UI.

## Argumentos

- `$1`: Nome da Feature (ex: user-auth)
- `$2`: Nome da user story (ex: us-01)

## Workflow

Para o processo completo de desenvolvimento, SEMPRE veja [workflow.md](workflow.md).
É EXTRITAMENTE IMPORTANTE SEGUIR TODO O WORKFLOW

### Resumo do Fluxo

1. Carregar user story de `dev/features-plan/$1.json`
2. Analisar codebase existente
3. Criar plano com `TodoWriter`
4. Implementar backend (Laravel/PHP)
5. Implementar frontend (Livewire/Blade/Flux)
6. Qualidade: Pint + PHPStan nível 6
7. Simplificar com laravel-simplifier
8. Escrever testes (Pest)
9. Commit e Push
10. Criar ou Atualizar a PR
11. Atualizar status para `done`
12. Se a user story foi a última da feature, atualize o @dev/PRD.md



## Guidelines

ESSAS GUIDELINES PRECISAM SEMPRE SER SEGUIDAS

| Stack | Referência |
|-------|------------|
| PHP 8.4+ | [php.md](guidelines/php.md) |
| Laravel 12 | [laravel.md](guidelines/laravel.md) |
| Pest | [pest.md](guidelines/pest.md) |
| Livewire v3 | [livewire.md](guidelines/livewire.md) |
| Tailwind v4 | [tailwindcss.md](guidelines/tailwindcss.md) |
| Flux UI | [flux-ui.md](guidelines/flux-ui.md) |

## Comandos Essenciais

```bash
# Testes
php artisan test --filter=NomeDaFeature

# Qualidade
vendor/bin/pint --dirty
vendor/bin/phpstan analyse --level=6 --memory-limit=512M

# Git
git add . && git commit -m "feat($1): implementa $2"
git push origin HEAD
```

## Critérios de Saída

- [ ] Funcionalidade implementada conforme critérios de aceite
- [ ] PHPStan nível 6 sem erros
- [ ] Pint aplicado
- [ ] Testes cobrindo critérios de aceite
- [ ] PR criada
- [ ] Status atualizado para `done`