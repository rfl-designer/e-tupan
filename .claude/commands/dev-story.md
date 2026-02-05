---
description: Desenvolve uma user story completa com testes, código e PR
argument-hint: <feature-name> <story-name>
allowed-tools:
  - Read
  - Write
  - Edit
  - Bash
  - Glob
  - Grep
  - Task
  - AskUserQuestion
  - TodoWrite
---

# Desenvolver User Story

## Contexto

Você está desenvolvendo uma user story completa, desde testes até a criação da PR.

## Argumentos

- `$1`: Nome da feature (ex: user-auth)
- `$2`: Nome da user story (ex: us-01)

Ambos são obrigatórios. Se não fornecidos, peça ao usuário.

## Fluxo de Desenvolvimento

### 1. Carregar User Story

```
Arquivo: dev/features-plan/$1.json
```

- Leia o arquivo JSON da feature
- Encontre a user story pelo name ($2)
- Se não encontrar, liste as disponíveis e peça para escolher
- Verifique o status (não desenvolver se já está `done`)

### 2. Analisar Codebase

Use o TodoWrite para criar um plano de tarefas baseado nos critérios de aceite.

Analise:
- Estrutura existente de Controllers, Models, Views
- Padrões de código utilizados
- Testes existentes similares
- Componentes Livewire/Flux disponíveis

SEMPRE Retorne ao usuário:
- A User Stories
- O plano que será seguido

### 3. Criar Testes Primeiro (TDD)

Para cada critério de aceite, crie um teste Pest:

```bash
php artisan make:test Feature/<FeatureName>/<StoryName>Test --pest
```

Estrutura do teste:
```php
it('critério de aceite aqui', function () {
    // Arrange
    // Act
    // Assert
});
```

### 4. Desenvolver Recursos

Crie os recursos necessários usando comandos Artisan:
- `php artisan make:model --migration --factory`
- `php artisan make:controller`
- `php artisan make:livewire`
- `php artisan make:request`
- etc.

Siga as convenções do CLAUDE-EXAMPLE.md (Laravel Boost Guidelines).

### 5. Executar Testes

```bash
php artisan test tests/Feature/<FeatureName>/<StoryName>Test.php
```

- Se falhar, corrija o código e execute novamente
- Continue até todos passarem

### 6. Qualidade de Código

Após testes passarem:

```bash
# Formatar código
vendor/bin/pint --dirty

# Análise estática (nível 6)
vendor/bin/phpstan analyse --level=6 --memory-limit=512M
```

Se PHPStan reportar erros, corrija-os.
