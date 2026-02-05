# AGENTS Guidelines for this repository

## Project Overview

Estamos criando uma projeto para estruturar um orquestração de agents de inteligência artificial para **Claude Code**, vamos criar regras, hooks, template de `CLAUDE.md`, nomear arquivos e criar um repositório para reutilizar a estrutura.

## Guidelines

### MCP Tools

- **Laravel Boost**: Ferramentas MCP para trabalhar com Laravel (tinker, database, logs, artisan, docs). [Laravel Boost Guidelines](.claude/rules/laravel-boost.md)
- **Herd**: MCP do Laravel Herd para ambiente de desenvolvimento PHP. [Herd Guidelines](.claude/rules/herd.md)

### Laravel Framework

- **Laravel Core**: Convenções e boas práticas do Laravel (Eloquent, controllers, validation, queues). [Laravel Core Guidelines](.claude/rules/laravel-core.md)
- **Laravel 12**: Estrutura e mudanças específicas do Laravel 12 (middleware, bootstrap/app.php). [Laravel 12 Guidelines](.claude/rules/laravel-12.md)
- **Migrations**: Convenções para criar e modificar migrations (foreign keys, indexes). [Migrations Guidelines](.claude/rules/migrations.md)
- **Fortify**: Backend de autenticação headless (login, registro, 2FA, reset de senha). [Fortify Guidelines](.claude/rules/fortify.md)

### Frontend

- **Livewire**: Componentes reativos com PHP (wire:model, wire:click, SFC, MFC). [Livewire Guidelines](.claude/rules/livewire.md)
- **Alpine.js**: Framework JS para interações client-side com Livewire (x-data, x-show, $wire). [Alpine Guidelines](.claude/rules/alpine.md)
- **Flux UI**: Biblioteca de componentes Pro para Livewire. [Flux UI Guidelines](.claude/rules/flux-ui.md)
- **Tailwind CSS**: Framework CSS utility-first v4 (dark mode, @theme, gap). [Tailwind Guidelines](.claude/rules/tailwind.md)

### Code Quality

- **PHP**: Convenções PHP 8+ (constructor promotion, type hints, enums). [PHP Guidelines](.claude/rules/php.md)
- **Pint**: Formatador de código Laravel (`vendor/bin/pint --dirty`). [Pint Guidelines](.claude/rules/pint.md)
- **Test**: Testes com Pest 4 (feature, unit, browser, datasets). [Test Guidelines](.claude/rules/test.md)
- **Security**: Boas práticas de segurança (OWASP, validação, XSS, CSRF, SQL injection). [Security Guidelines](.claude/rules/security.md)
- **Git**: Convenções de commit, branch e workflow. [Git Guidelines](.claude/rules/git.md)

## Agents

Agents especializados para automatizar tarefas comuns no desenvolvimento Laravel.

### Code Quality Agents

| Agent | Descrição | Uso |
|-------|-----------|-----|
| **code-reviewer** | Revisa código verificando segurança, performance e padrões Laravel | Antes de commits |
| **refactor-analyzer** | Analisa código e sugere refatorações (Services, Actions, Scopes) | Melhorar código existente |
| **performance-auditor** | Identifica N+1 queries, índices faltando, oportunidades de cache | Otimização |

### Generator Agents

| Agent | Descrição | Uso |
|-------|-----------|-----|
| **test-generator** | Gera testes Pest para controllers, models e Livewire components | Criar testes |
| **livewire-builder** | Cria componentes Livewire com Flux UI e Alpine.js | Criar componentes |
| **api-documenter** | Gera documentação OpenAPI/Swagger a partir de rotas e resources | Documentar APIs |

### Validation Agents

| Agent | Descrição | Uso |
|-------|-----------|-----|
| **migration-validator** | Valida migrations verificando reversibilidade e padrões Laravel 12 | Antes de migrate |

### Como Usar

Os agents podem ser invocados através da ferramenta Task:
```
Use o agent code-reviewer para revisar o código em app/Http/Controllers/
```

Ou solicite diretamente:
```
Revise o código antes do commit (usa code-reviewer)
Gere testes para o UserController (usa test-generator)
Valide a migration antes de executar (usa migration-validator)
```
