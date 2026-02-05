# Security

- Sempre valide e sanitize input do usuário.
- Nunca confie em dados vindos do cliente.

## OWASP Top 10

### 1. Injection (SQL, Command, etc.)

**Sempre use:**
- Eloquent ORM ou Query Builder (parâmetros são escapados automaticamente)
- Prepared statements para queries raw

```php
// CORRETO
User::where('email', $email)->first();
DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// ERRADO - vulnerável a SQL injection
DB::select("SELECT * FROM users WHERE email = '$email'");
```

### 2. Cross-Site Scripting (XSS)

**Blade escapa automaticamente com `{{ }}`:**

```blade
{{-- CORRETO - escapado --}}
{{ $user->name }}

{{-- CUIDADO - não escapado, use apenas para HTML confiável --}}
{!! $trustedHtml !!}
```

### 3. Cross-Site Request Forgery (CSRF)

**Laravel protege automaticamente. Sempre inclua:**

```blade
<form method="POST">
    @csrf
    <!-- campos -->
</form>
```

### 4. Mass Assignment

**Defina `$fillable` ou `$guarded` em models:**

```php
// CORRETO
protected $fillable = ['name', 'email'];

// ou
protected $guarded = ['id', 'is_admin'];

// NUNCA faça
User::create($request->all()); // sem $fillable definido
```

## Validação

### Form Requests

```php
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed'],
        ];
    }
}
```

### Regras Importantes

| Regra | Uso |
|-------|-----|
| `required` | Campo obrigatório |
| `string` | Deve ser string |
| `integer` | Deve ser inteiro |
| `email` | Formato de email válido |
| `max:n` | Tamanho máximo |
| `in:a,b,c` | Valores permitidos |
| `exists:table,column` | Deve existir no banco |
| `unique:table,column` | Deve ser único |

## Autorização

### Policies

```php
// Verificar no controller
$this->authorize('update', $post);

// No Blade
@can('update', $post)
    <button>Editar</button>
@endcan
```

### Gates

```php
// Definir
Gate::define('admin', fn (User $user) => $user->is_admin);

// Usar
if (Gate::allows('admin')) {
    // ...
}
```

## Arquivos Sensíveis

**NUNCA commitar ou expor:**

- `.env` - variáveis de ambiente
- `auth.json` - credenciais Composer
- `storage/oauth-*.key` - chaves OAuth
- Arquivos com API keys, tokens, senhas

**Use `.env.example` como template sem valores reais.**

## Senhas

```php
// CORRETO - usa bcrypt automaticamente
$user->password = Hash::make($password);

// Verificar
if (Hash::check($password, $user->password)) {
    // ...
}

// NUNCA armazene senhas em texto plano
```

## Rate Limiting

```php
// Em routes
Route::middleware('throttle:60,1')->group(function () {
    // 60 requests por minuto
});
```

## Headers de Segurança

Laravel inclui middleware para headers. Verifique `bootstrap/app.php`.

## Common Pitfalls

- Usar `{!! !!}` com dados do usuário (XSS)
- Queries raw com concatenação de strings (SQL Injection)
- `$request->all()` sem `$fillable` definido (Mass Assignment)
- Esquecer `@csrf` em formulários
- Armazenar senhas sem hash
- Commitar `.env` ou arquivos com credenciais
- Não validar uploads de arquivo (tipo, tamanho)
- Expor IDs internos em URLs sem autorização
