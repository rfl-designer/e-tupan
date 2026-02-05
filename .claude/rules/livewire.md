---
name: livewire-guidelines
description: Componentes reativos com PHP (wire:model, wire:click, SFC, MFC, islands, async actions).
---
# Livewire

- Livewire allows you to build dynamic, reactive interfaces using only PHP — no JavaScript required.
- Instead of writing frontend code in JavaScript frameworks, you use Alpine.js to build the UI when client-side interactions are required.
- State lives on the server; the UI reflects it. Validate and authorize in actions (they're like HTTP requests).

## Documentation

Use `search-docs` for detailed Livewire 4 patterns and documentation.

## Basic Usage

### Creating Components

<code-snippet name="Component Creation Commands" lang="bash">

# Single-file component (default in v4)

{{ $assist->artisanCommand('make:livewire create-post') }}

# Multi-file component

{{ $assist->artisanCommand('make:livewire create-post --mfc') }}

# Class-based component (v3 style)

{{ $assist->artisanCommand('make:livewire create-post --class') }}

# With namespace

{{ $assist->artisanCommand('make:livewire Posts/CreatePost') }}

</code-snippet>

### Converting Between Formats

Use `php artisan livewire:convert create-post` to convert between single-file, multi-file, and class-based formats.

### Component Format Reference

| Format | Flag | Structure |
|--------|------|-----------|
| Single-file (SFC) | default | PHP + Blade in one file |
| Multi-file (MFC) | `--mfc` | Separate PHP class, Blade, JS, tests |
| Class-based | `--class` | Traditional v3 style class |
| View-based | ⚡ prefix | Blade-only with functional state |

### Single-File Component Example

<code-snippet name="Single-File Component Example" lang="php">

<?php
use Livewire\Component;

new class extends Component {
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
}
?>

<div>
    <button wire:click="increment">Count: @{{ $count }}</button>
</div>

</code-snippet>

## Livewire 4 Specifics

### Key Changes From Livewire 3

These things changed in Livewire 4, but may not have been updated in this application. Verify this application's setup to ensure you follow existing conventions.

- Use `Route::livewire()` for full-page components; config keys renamed: `layout` → `component_layout`, `lazy_placeholder` → `component_placeholder`.
- `wire:model` now ignores child events by default (use `wire:model.deep` for old behavior); `wire:scroll` renamed to `wire:navigate:scroll`.
- Component tags must be properly closed; `wire:transition` now uses View Transitions API (modifiers removed).
- JavaScript: `$wire.$js('name', fn)` → `$wire.$js.name = fn`; `commit`/`request` hooks → `interceptMessage()`/`interceptRequest()`.

### New Features

- Component formats: single-file (SFC), multi-file (MFC), view-based components.
- Islands (`@island`) for isolated updates; async actions (`wire:click.async`, `#[Async]`) for parallel execution.
- Deferred/bundled loading: `defer`, `lazy.bundle` for optimized component loading.

| Feature | Usage | Purpose |
|---------|-------|---------|
| Islands | `@island(name: 'stats')` | Isolated update regions |
| Async | `wire:click.async` or `#[Async]` | Non-blocking actions |
| Deferred | `defer` attribute | Load after page render |
| Bundled | `lazy.bundle` | Load multiple together |

### New Directives

- `wire:sort`, `wire:intersect`, `wire:ref`, `.renderless`, `.preserve-scroll` are available for use.
- `data-loading` attribute automatically added to elements triggering network requests.

| Directive | Purpose |
|-----------|---------|
| `wire:sort` | Drag-and-drop sorting |
| `wire:intersect` | Viewport intersection detection |
| `wire:ref` | Element references for JS |
| `.renderless` | Component without rendering |
| `.preserve-scroll` | Preserve scroll position |

## Best Practices

- Always use `wire:key` in loops
- Use `wire:loading` for loading states
- Use `wire:model.live` for instant updates (default is debounced)
- Validate and authorize in actions (treat like HTTP requests)

## Configuration

- `smart_wire_keys` defaults to `true`; new configs: `component_locations`, `component_namespaces`, `make_command`, `csp_safe`.

## Alpine & JavaScript

- `wire:transition` uses browser View Transitions API; `$errors` and `$intercept` magic properties available.
- Non-blocking `wire:poll` and parallel `wire:model.live` updates improve performance.

For interceptors and hooks, see [reference/javascript-hooks.md](reference/javascript-hooks.md).

## Testing

<code-snippet name="Testing Example" lang="php">

Livewire::test(Counter::class)
    ->assertSet('count', 0)
    ->call('increment')
    ->assertSet('count', 1);

</code-snippet>

## Verification

1. Browser console: Check for JS errors
2. Network tab: Verify Livewire requests return 200
3. Ensure `wire:key` on all `@foreach` loops

## Common Pitfalls

- Missing `wire:key` in loops → unexpected re-rendering
- Expecting `wire:model` real-time → use `wire:model.live`
- Unclosed component tags → syntax errors in v4
- Using deprecated config keys or JS hooks
- Including Alpine.js separately (already bundled in Livewire 4)

# Livewire 4 JavaScript Integration

## Interceptor System (v4)

### Intercept Messages

```js
Livewire.interceptMessage(({ component, message, onFinish, onSuccess, onError }) => {
    onFinish(() => { /* After response, before processing */ });
    onSuccess(({ payload }) => { /* payload.snapshot, payload.effects */ });
    onError(() => { /* Server errors */ });
});
```

### Intercept Requests

```js
Livewire.interceptRequest(({ request, onResponse, onSuccess, onError, onFailure }) => {
    onResponse(({ response }) => { /* When received */ });
    onSuccess(({ response, responseJson }) => { /* Success */ });
    onError(({ response, responseBody, preventDefault }) => { /* 4xx/5xx */ });
    onFailure(({ error }) => { /* Network failures */ });
});
```

### Component-Scoped Interceptors

```blade
<script>
    this.$intercept('save', ({ component, onSuccess }) => {
        onSuccess(() => console.log('Saved!'));
    });
</script>
```

## Magic Properties

- `$errors` - Access validation errors from JavaScript
- `$intercept` - Component-scoped interceptors