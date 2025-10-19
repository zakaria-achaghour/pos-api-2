## Repo overview

This repository is a Laravel 12 API for a POS (point-of-sale) system. Key application code lives under `app/` (controllers, models, services). Public entry is `public/index.php`. Routes are registered in `routes/api.php` (primary API surface).

Important files to reference:
- `composer.json` — PHP requirements, autoload namespaces (App, Domain, Application, Infrastructure), and helpful scripts (`composer test`, `composer dev`).
- `routes/api.php` — full API surface (auth, admin, protected tenant middleware). Use this to find endpoints and typical controller names.
- `docker-compose.yml` and `Dockerfile` — recommended dev environment: services `app`, `nginx`, `postgres`.
- `phpunit.xml` and `tests/` — test configuration: in-memory sqlite for unit tests; run tests with `composer run test` or `php artisan test`.

## Big-picture architecture (what to know)

- Laravel 12 application using multi-folder domain separation: `app/Models`, `app/Services`, and `app/Infrastructure` (PSR-4 namespaces defined in `composer.json`). Look for business rules in `app/Services` and cross-cutting infra in `app/Infrastructure`.
- Multi-tenant design: several routes are protected by a `tenant` middleware (see `routes/api.php`) and an `Admin` area guarded by `role:SuperAdmin`. Respect middleware when adding endpoints.
- Authentication uses JWT (`tymon/jwt-auth`) — check `App\Http\Controllers\Api\AuthController` for usage patterns.
- Background jobs and queue handling: `composer.json` dev `dev` script runs `php artisan queue:listen` and `php artisan pail`. Jobs are typically synchronous in tests (see `phpunit.xml` queue connection `sync`).

## Developer workflows & commands

- Common commands (run from repo root):
  - Install PHP deps: `composer install`
  - Start Docker stack (dev): `docker compose up --build` — services: `app`, `nginx`, `postgres`.
  - Start local dev without Docker: `composer run dev` runs `php artisan serve`, queue listener, pail, and vite concurrently (see `composer.json` scripts).
  - Run tests: `composer run test` or `php artisan test`.
  - Linter/formatter: `vendor/bin/pint` (installed via `composer` dev deps) — used indirectly by `composer` scripts.

## Project-specific conventions and patterns

- Namespaces: `App\` maps to `app/`; other root namespaces include `Domain\`, `Application\`, and `Infrastructure\` (see `composer.json` autoload). When adding new classes, place them in the matching namespace and run `composer dump-autoload`.
- Controllers are grouped by area:
  - `App\Http\Controllers\Api\` for public API controllers (see `routes/api.php`).
  - `App\Http\Controllers\Admin\` for admin/tenant management.
- Middleware: `auth:api` (JWT) and a custom `tenant` middleware enforce tenant scope. New endpoints that operate on tenant-scoped data must use `tenant` middleware.
- Database: Primary dev DB in Docker uses Postgres (see `docker-compose.yml`), while tests use sqlite in-memory (`phpunit.xml`). When writing tests, prefer in-memory DB and sync queue.
- Jobs & queues: In CI/test mode queues are `sync`. In dev, queue worker is started by the `dev` script. Avoid writing tests that depend on background queue processing unless explicitly handled.

## Integration points & external dependencies

- JWT auth: `tymon/jwt-auth` — authenticate via `/login` and protect routes with `auth:api` guard.
- File exports/PDFs: `barryvdh/laravel-dompdf` is used for PDF generation (see controllers under `AttendanceController` exporting PDFs).
- Swagger / API docs: `darkaonline/l5-swagger` is present; API docs are generated into `storage/api-docs`.
- Permissions: `spatie/laravel-permission` used for role checks (e.g., `role:SuperAdmin` in admin routes).

## Typical change patterns & examples

- Adding an API endpoint:
  1. Add route to `routes/api.php` (match existing route group/middleware).
  2. Create controller under `App\Http\Controllers\Api` (or Admin for tenant admin).
  3. Add a model in `app/Models` and any service logic in `app/Services` or `app/Infrastructure` as appropriate.
  4. Register any new bindings via service provider if needed; prefer constructor injection for services.

- Writing tests:
  - Unit/Feature tests live in `tests/Unit` and `tests/Feature`.
  - Tests use sqlite in-memory by default — do not rely on Postgres-specific SQL in tests.

## Files to read first when onboarding
- `routes/api.php` — to understand the API surface and middleware usage.
- `composer.json` — scripts, namespaces, and top-level requirements.
- `docker-compose.yml` & `Dockerfile` — local dev stack.
- `app/Services` & `app/Infrastructure` — where domain logic usually lives.
- `phpunit.xml` and `tests/` — testing patterns and environment.

## Do not assume / gotchas

- Tests use an in-memory sqlite DB — behavior differs from Postgres (e.g., JSON/array column handling). Favor adapter-agnostic queries in tests.
- Some dev scripts expect `npm`/`vite` for asset tooling; API work usually doesn't require building frontend assets but `composer dev` triggers it.
- Route ordering matters: specific routes (e.g., `/tables/analytics`) must come before resource routes (e.g., `apiResource('tables')`) to avoid parameter conflicts where "analytics" gets treated as a table ID.

---
If anything in these instructions is unclear or you want more detail about a specific area (database models, tenancy, JWT handling, or testing patterns), tell me which part and I'll expand or adjust the file.
