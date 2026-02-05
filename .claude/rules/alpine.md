# Alpine.js

- Alpine.js é o framework JavaScript recomendado para interações client-side com Livewire.
- Livewire 4 já inclui Alpine.js bundled - não instale separadamente.

## Documentation

Use `search-docs` for detailed Alpine.js patterns and documentation.

## Basic Usage

### Diretivas Principais

| Diretiva | Uso |
|----------|-----|
| `x-data` | Define escopo reativo |
| `x-show` | Toggle visibilidade (CSS) |
| `x-if` | Renderização condicional (DOM) |
| `x-for` | Loop sobre arrays |
| `x-on` / `@` | Event listeners |
| `x-bind` / `:` | Bind atributos |
| `x-model` | Two-way binding |
| `x-text` | Define innerText |
| `x-html` | Define innerHTML |
| `x-ref` | Referência a elementos |
| `x-cloak` | Esconde até Alpine inicializar |

### Exemplo Básico

```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>
        Conteúdo
    </div>
</div>
```

## Integração com Livewire

### Acessando Dados do Livewire

```html
<div x-data="{ localValue: $wire.serverValue }">
    <input x-model="localValue" @change="$wire.serverValue = localValue">
</div>
```

### Magic Properties

| Property | Descrição |
|----------|-----------|
| `$wire` | Acessa o componente Livewire |
| `$wire.property` | Lê propriedade do servidor |
| `$wire.method()` | Chama método do servidor |
| `$wire.$refresh()` | Força re-render |
| `$wire.$set('prop', value)` | Define propriedade |

### Entangle (Sincronização)

```html
<div x-data="{ count: $wire.entangle('count') }">
    <button @click="count++">{{ count }}</button>
</div>
```

Use `.live` para sincronização imediata:

```html
<div x-data="{ count: $wire.entangle('count').live }">
```

## Padrões Comuns

### Dropdown

```html
<div x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open">Menu</button>
    <div x-show="open" x-transition>
        <!-- items -->
    </div>
</div>
```

### Modal

```html
<div x-data="{ show: false }">
    <button @click="show = true">Abrir</button>
    <div x-show="show" x-transition.opacity @keydown.escape.window="show = false">
        <div @click.outside="show = false">
            <!-- conteúdo -->
        </div>
    </div>
</div>
```

### Loading State

```html
<button
    @click="$wire.save()"
    :disabled="$wire.$loading"
    :class="{ 'opacity-50': $wire.$loading }"
>
    <span x-show="!$wire.$loading">Salvar</span>
    <span x-show="$wire.$loading">Salvando...</span>
</button>
```

## Common Pitfalls

- Instalar Alpine.js separadamente quando Livewire 4 já inclui
- Usar `x-if` quando `x-show` é suficiente (x-if remove do DOM)
- Esquecer `@click.outside` em dropdowns/modals
- Não usar `x-cloak` para evitar flash de conteúdo
- Confundir `$wire.property` (leitura) com `$wire.$set()` (escrita)
