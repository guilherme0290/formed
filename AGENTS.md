# Repository Guidelines

This is a Laravel (PHP 8.2+) application with a Vite/Tailwind frontend. Use the conventions and tooling below to keep changes consistent and easy to review.

## Project Structure & Module Organization

- `app/`: application code (controllers in `app/Http/Controllers/*`, domain logic in `app/Services`, models in `app/Models`).
- `routes/`: HTTP routes (`web.php`, `auth.php`).
- `resources/views/`: Blade templates, organized by area (e.g. `resources/views/comercial/`, `resources/views/clientes/`, `resources/views/operacional/`).
- `resources/js/`, `resources/css/`: frontend entrypoints built by Vite.
- `database/`: migrations, factories, seeders.
- `tests/`: PHPUnit tests (`tests/Feature`, `tests/Unit`).

## Build, Test, and Development Commands

- `composer setup`: bootstrap a local install (creates `.env`, generates app key, runs migrations, installs JS deps, builds assets).
- `composer dev`: runs the local stack via `concurrently` (server, queue listener, logs via pail, and Vite).
- `composer test`: clears config and runs `php artisan test`.
- `npm run dev`: starts the Vite dev server (assets/hot reload).
- `npm run build`: builds production assets into `public/build`.

## Coding Style & Naming Conventions

- Indentation: 4 spaces (see `.editorconfig`); YAML uses 2 spaces.
- PHP: follow Laravel conventions; format with Pint: `./vendor/bin/pint`.
- Naming: classes `StudlyCase`, methods/variables `camelCase`. Keep namespaces aligned with folders (e.g. `App\\Http\\Controllers\\Comercial\\*`).
- Blade: prefer reusable components in `resources/views/components/` and reference them as `<x-foo-bar />`.

## Testing Guidelines

- Framework: PHPUnit via `php artisan test`.
- Naming: `*Test.php` under `tests/Feature` or `tests/Unit`.
- Quick runs: `php artisan test --filter ProfileTest`.
- Tests use in-memory SQLite by default (see `phpunit.xml`); use factories for setup when possible.

## Commit & Pull Request Guidelines

- Commit messages in this repo are short and descriptive (often Portuguese, e.g. `melhoria tabela de preco`). Keep the subject focused on one change; consider prefixing the area (`comercial: ...`, `cliente: ...`).
- PRs should include: a summary, how to test, screenshots for UI changes, and notes about migrations/seed data and any new `.env` keys (also update `.env.example`).

## Security & Configuration Tips

- Treat `.env` as local-only; don’t commit secrets/credentials.
- When adding config, document defaults in `.env.example` and validate usage in code.
