# AGENTS.md — FinTrack API

## Project Overview

Personal finance tracking REST API built with Laravel 13 (PHP 8.3), PostgreSQL, Sanctum auth, and Pest testing. API docs served via Scalar at `/scalar`.

## Build / Lint / Test Commands

```bash
# Run all tests
php artisan test
php artisan test --compact

# Run a single test by name filter
php artisan test --filter=test_name

# Run tests in a specific file
php artisan test tests/Feature/AuthTest.php

# Run only feature or unit tests
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Lint / format changed PHP files (ALWAYS run before finalizing)
vendor/bin/pint --dirty --format agent

# Migrations
php artisan migrate:fresh --seed        # Reset DB, run all migrations + seeders
php artisan migrate                      # Run pending migrations

# Dev server
composer run dev                         # Concurrent: serve + queue + vite
php artisan serve                        # API only
```

## Tech Stack & Versions

- **PHP**: 8.3 | **Laravel**: 13 | **Database**: PostgreSQL (via Laragon)
- **Auth**: laravel/sanctum v4 (SPA + API token)
- **Testing**: pestphp/pest v4, phpunit/phpunit v12
- **Formatter**: laravel/pint v1
- **API Docs**: scalar/laravel v0.2 (at `/scalar`, spec at `/api/v1/openapi`)
- **Frontend CSS**: tailwindcss v4 (when applicable)

## Architecture & Directory Structure

```
app/
  Enums/                    # Backed string enums (TransactionType, WalletType, etc.)
  Http/
    Controllers/
      Controller.php        # Base: successResponse() / errorResponse() helpers
      Api/V1/               # All API controllers (versioned)
    Middleware/              # ForceJsonResponse, etc.
    Requests/Auth/           # Form requests namespaced by domain
    Resources/               # Eloquent API resources
  Models/                    # Eloquent models with #[Fillable] / #[Hidden] attributes
bootstrap/
  app.php                    # Middleware registration, exception handling
config/
  scalar.php                 # Scalar API docs config
database/
  factories/                 # Model factories
  migrations/                # Ordered by timestamp prefix (100001..100009)
  seeders/                   # CurrencySeeder, CategorySeeder, DatabaseSeeder
routes/
  api.php                    # API v1 routes under prefix('v1')
  web.php                    # Web routes (minimal)
tests/
  Feature/                   # Feature tests (HTTP/integration)
  Unit/                      # Unit tests
  Pest.php                   # Pest bootstrap, extends TestCase for Feature/
```

## Code Style Guidelines

### PHP & Imports

- Always use curly braces for control structures, even single-line bodies.
- Use PHP 8 constructor property promotion. No empty `__construct()`.
- Explicit return types and parameter type hints on every method: `function store(Request $request): JsonResponse`.
- Group imports: framework first, then `App\`, then third-party. Alphabetical within groups.
- No unused imports.

### Enums

- Location: `app/Enums/`
- Backed string enums: `enum TransactionType: string`
- TitleCase keys, snake_case values: `case Income = 'income';`
- Database stores the string value; CHECK constraints enforce valid values at the DB level.

### Models

- Use `#[Fillable([...])]` and `#[Hidden([...])]` attributes (Laravel 13 style).
- Define `casts()` method (not `$casts` property) for attribute casting.
- Use `HasApiTokens` trait for Sanctum-authenticatable models.
- Mirror migration column defaults in `$attributes` when applicable.

### Controllers

- All API controllers extend `App\Http\Controllers\Controller` (base with response helpers).
- API controllers live in `App\Http\Controllers\Api\V1\`.
- Use `$this->successResponse($data, $message, $code)` and `$this->errorResponse($message, $code)`.
- Type-hint Form Requests for auto-validation: `public function register(RegisterRequest $request)`.
- For simple inline validation, use `$request->validate([...])`.
- Keep controller methods lean — extract to service/action classes when exceeding ~15 lines.

### Form Requests

- Namespace by domain: `App\Http\Requests\Auth\RegisterRequest`.
- `authorize()` returns `true` (policy-based auth handled via middleware).
- Validation rules use array syntax: `['required', 'string', 'max:255']`.

### API Resources

- Location: `App\Http\Resources\`.
- Explicit `toArray()` mapping every field (never `parent::toArray()`).
- Return only the fields needed by the API consumer.

### Migrations

- Generate with `php artisan make:migration`.
- Use `constrained()` for foreign keys: `$table->foreignId('user_id')->constrained()->cascadeOnDelete()`.
- Non-standard FK references: `$table->foreign('currency_code')->references('code')->on('currencies')`.
- PostgreSQL CHECK constraints via `DB::statement("ALTER TABLE ... ADD CONSTRAINT ... CHECK (...)")`.
- Partial indexes via raw SQL for PostgreSQL-specific features.
- Always implement reversible `down()`.
- One concern per migration — never mix DDL and DML.

### Routes

- API routes in `routes/api.php`, versioned under `Route::prefix('v1')`.
- Protected routes grouped under `Route::middleware('auth:sanctum')`.
- Use array callable syntax: `[AuthController::class, 'register']`.

### Response Format

Success:
```json
{ "message": "Success", "data": { ... } }
```

Auth token response:
```json
{ "message": "...", "data": { "user": { ... }, "token": "...", "token_type": "Bearer" } }
```

Error (validation):
```json
{ "message": "Error description", "errors": { "field": ["message"] } }
```

### Error Handling

- Validation errors: throw `ValidationException::withMessages()` or let Form Request handle it.
- Auth failures: Sanctum returns 401 automatically.
- Use Laravel's exception handling in `bootstrap/app.php` for custom rendering.
- API routes always return JSON (enforced by `ForceJsonResponse` middleware).

## Testing Conventions

- Pest PHP syntax (not PHPUnit): `test('description', function () { ... })`.
- Feature tests extend `Tests\TestCase` via Pest bootstrap (`pest()->extend(TestCase::class)->in('Feature')`).
- Use factories for test data: `User::factory()->create([...])`.
- Use `fake()` helper (not `$this->faker`).
- Create tests with `php artisan make:test --pest {name}` (feature) or `--pest --unit` (unit).
- Never delete tests without approval.

## Naming Conventions

| Entity | Convention | Example |
|---|---|---|
| Controllers | PascalCase | `AuthController` |
| Methods | camelCase | `updateProfile` |
| Models | Singular PascalCase | `User`, `Wallet` |
| Migrations | snake_case | `create_wallets_table` |
| Tables | Plural snake_case | `recurring_transactions` |
| Columns | snake_case | `default_currency_code` |
| Enums | Singular PascalCase, TitleCase keys | `WalletType::CreditCard` |
| Routes | kebab-case prefixes | `/v1/auth/password` |
| Form Requests | PascalCase | `RegisterRequest` |
| Resources | PascalCase | `UserResource` |

## Key Reminders

- Run `vendor/bin/pint --dirty --format agent` after modifying any PHP file.
- Do NOT add comments unless requested.
- Do NOT commit unless explicitly asked.
- Do NOT create documentation files unless asked.
- Use `php artisan make:` commands to scaffold files — always pass `--no-interaction`.
- Check sibling files for existing patterns before creating new files.
