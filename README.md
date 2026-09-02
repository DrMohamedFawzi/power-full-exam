# Aegis-X

Arabic-first online examination platform with integrity enforcement — exam authoring,
a locked-down exam runner, real-time proctoring, and an application-security console.

**Laravel 13 · PHP 8.4 · Blade + Tailwind 4 + DaisyUI 5 + Alpine.js · MySQL**

## Getting started

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate

# create the database, then:
php artisan migrate --seed

npm start               # runs the app server, queue worker, and Vite together
```

`npm start` runs `php artisan serve`, `php artisan queue:listen` (required —
proctoring runs on the queue), and `npm run dev` concurrently, killing all three if
any one exits. For production assets, use `npm run build` instead.

Set `GEMINI_API_KEY` in `.env` to enable AI exam generation. Everything else works without it.

## Roles

| Role | Does |
|---|---|
| **Student** | Joins classrooms by code, sits exams in the proctored runner, reviews results and trusted devices |
| **Teacher** | Creates classrooms and exams (manually or with AI), approves enrollment, monitors live sessions, grades and reports |
| **Institution** | Approves teachers, oversees classrooms and exams across the organisation, runs the security console |

## Documentation

| Document | What it covers |
|---|---|
| [CLAUDE.md](CLAUDE.md) | Working rules — read first when contributing |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Module layout, layering rules, request lifecycle, proctoring pipeline |
| [docs/DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md) | Tokens, components, RTL and accessibility contract |
| [docs/DATA_MODEL.md](docs/DATA_MODEL.md) | Entities, relationships, legacy mapping |

## Architecture in one paragraph

A modular monolith. Five bounded contexts — `Identity`, `Academics`, `Assessment`,
`Proctoring`, `Overwatch` — live under `app/Modules/`, each owning its models, actions
(writes), queries (reads), policies, HTTP layer and routes. Dependencies only point
downward. Controllers stay thin, business logic lives in Actions, authorization lives in
Policies, and the UI is Blade components over a single DaisyUI theme pair, RTL by default.

## Legacy

This replaces a single-file PHP application (`_legacy/api.php` — 2,964 lines, ~35 `action`
branches), six standalone HTML dashboards, and a Python/RabbitMQ proctoring worker. The old
sources are kept under `_legacy/` as reference only; nothing in the application reads them.
See [docs/ARCHITECTURE.md §9](docs/ARCHITECTURE.md) for what was dropped and why.

> **Security note:** the legacy `config.json` sits in this repository's git history with a
> live Gemini API key and the old JWT secret. Both must be rotated.
