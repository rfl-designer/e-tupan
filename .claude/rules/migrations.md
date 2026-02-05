# Migrations

- Use `php artisan make:migration` para criar migrations.
- Migrations devem ser reversíveis quando possível.

## Criação

### Comandos

```bash
# Criar tabela
php artisan make:migration create_posts_table

# Modificar tabela
php artisan make:migration add_status_to_posts_table

# Com model e factory
php artisan make:model Post -mf
```

### Nomenclatura

| Ação | Padrão |
|------|--------|
| Criar tabela | `create_{table}_table` |
| Adicionar coluna | `add_{column}_to_{table}_table` |
| Remover coluna | `remove_{column}_from_{table}_table` |
| Modificar coluna | `modify_{column}_in_{table}_table` |
| Renomear coluna | `rename_{old}_to_{new}_in_{table}_table` |

## Estrutura

### Criar Tabela

```php
public function up(): void
{
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->text('content');
        $table->enum('status', ['draft', 'published'])->default('draft');
        $table->timestamps();
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::dropIfExists('posts');
}
```

### Modificar Tabela

```php
public function up(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->string('slug')->unique()->after('title');
    });
}

public function down(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn('slug');
    });
}
```

## Laravel 12 - Modificação de Colunas

**CRÍTICO**: Ao modificar uma coluna, inclua TODOS os atributos anteriores:

```php
// ERRADO - perde nullable e default
$table->string('name', 100)->change();

// CORRETO - mantém todos os atributos
$table->string('name', 100)->nullable()->default('Guest')->change();
```

## Foreign Keys

### Convenções

```php
// Forma curta (usa convenções)
$table->foreignId('user_id')->constrained();

// Com cascade
$table->foreignId('user_id')->constrained()->cascadeOnDelete();

// Tabela customizada
$table->foreignId('author_id')->constrained('users');

// Nullable
$table->foreignId('category_id')->nullable()->constrained();
```

### Remover Foreign Key

```php
$table->dropForeign(['user_id']);
$table->dropColumn('user_id');
```

## Indexes

```php
// Único
$table->string('email')->unique();

// Index simples
$table->index('created_at');

// Index composto
$table->index(['user_id', 'status']);

// Remover index
$table->dropIndex(['created_at']);
```

## Execução

```bash
# Rodar migrations pendentes
php artisan migrate

# Rollback última batch
php artisan migrate:rollback

# Rollback e migrate
php artisan migrate:refresh

# Status das migrations
php artisan migrate:status
```

## Common Pitfalls

- Esquecer o método `down()` ou deixá-lo vazio
- Modificar coluna sem incluir todos os atributos anteriores (Laravel 12)
- Não definir `cascadeOnDelete()` em foreign keys quando apropriado
- Criar migrations que não podem ser revertidas
- Usar `dropColumn` em coluna com foreign key sem dropar a FK primeiro
- Esquecer de criar index em colunas usadas em WHERE/ORDER BY frequentes
