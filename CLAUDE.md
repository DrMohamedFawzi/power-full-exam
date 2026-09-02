# Aegis-X — Working Rules

Arabic-first (RTL) online examination platform with integrity enforcement.
Laravel 13 · PHP 8.4 · Blade · Tailwind 4 · DaisyUI 5 · Alpine.js · MySQL.

**Read [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) before writing code.** It is binding.
For anything visual, also read [docs/DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md).

## Layout

```
app/Modules/{Identity,Academics,Assessment,Proctoring,Overwatch,Dashboard}/
    Actions/ Queries/ Data/ Enums/ Models/ Policies/ Jobs/ Services/
    Http/{Controllers,Requests}/
    routes.php | Routes/*.php    auto-loaded, no registration needed
    Console/Commands/*.php       auto-registered Artisan commands
    Console/schedule.php         auto-loaded scheduled tasks
    events.php                   auto-registered event listeners
app/Support/                    cross-cutting: enums, middleware, navigation
resources/views/
    layouts/{app,guest,exam}.blade.php
    components/ui/              presentational primitives — compose these
    components/layout/          chrome
    {module}/                   pages
resources/js/components/        Alpine components, auto-registered by filename
docs/                           architecture, design system, data model
_legacy/                        the old PHP app. Read-only reference. Never import from it.
```

## Non-negotiables

- `declare(strict_types=1);` in every PHP file. Typed properties, params, returns.
- Controllers ≤ 60 lines: Form Request in, one Action or Query, response out. No queries, no
  business `if`s, no `DB::`.
- Writes live in `Actions/` (invokable, owns its transaction). Reads live in `Queries/`
  (returns exactly the shape the view needs).
- Models hold relations, casts, scopes. Nothing else.
- Authorization only in Policies (or the `role:` route middleware). Never
  `if ($user->role === 'teacher')` in a controller, action, or Blade file.
- Validation only in Form Requests.
- Every status/type column is a backed enum with `label()` (Arabic) and `color()` (DaisyUI token).
- No Eloquent access inside Blade. Pass prepared data.
- Any file over 350 lines gets decomposed.
- Secrets via `config('aegis.…')`, never `env()` outside `config/`.
- A module may only depend downward: Dashboard → Overwatch → Proctoring → Assessment →
  Academics → Identity. To talk *upward*, fire an event and listen for it in the higher
  module's `events.php` — never import upward. `Dashboard` is read-only (Queries + views).

## UI

- `<html dir="rtl" lang="ar">`. Logical properties only — `ms-*`/`me-*`/`ps-*`/`pe-*`,
  never `ml-*`/`mr-*`/`pl-*`/`pr-*`.
- All user-facing copy is Arabic. Code, comments, and identifiers stay English.
- Semantic colour tokens only (`primary`, `success`, `warning`, `error`, `base-*`). No hex,
  no `blue-500`, anywhere outside `resources/css/app.css`.
- Reuse `<x-ui.*>`: `card`, `stat`, `badge`, `input`, `select`, `textarea`, `modal`,
  `page-header`, `empty-state`, `integrity-meter`, `flash`, `toast-host`.
- Icons: `<x-heroicon-o-{name} class="size-5" />`.
- Wrap numbers/codes in `class="numeric"` so they read LTR inside Arabic text.
- Every list view needs a real empty state. Every destructive action needs confirmation.

## Commands

```bash
php artisan migrate:fresh --seed     # rebuild schema + demo data
php artisan queue:work               # proctoring pipeline (required for violations to land)
npm run dev                          # Vite
php artisan test                     # PHPUnit (in-memory SQLite; the app runs MySQL)
vendor/bin/pint                      # format before finishing
```

## Conventions

- Actions are imperative: `CreateExam`, `ApproveEnrollment`, `RevokeDevice`.
- Queries end in `Query`: `TeacherDashboardQuery`.
- Route names are `{role}.{resource}.{action}` — `teacher.exams.index`. Sidebar entries live
  in `app/Support/Navigation/Navigation.php` and are skipped automatically until the route exists.
- Tests are PHPUnit, in `tests/Feature/{Module}/`. New route ⇒ new feature test.
- Never edit a shipped migration; add a new one.

## Gotchas

- `config.json` in git history holds a **live Gemini API key and the old JWT secret**. Both
  need rotating. Nothing in the new codebase reads that file.
- The legacy app rebuilt its schema on every request and kept a JSON-file "fallback DB"
  under `_legacy/logs/`. Both are gone; MySQL is the only datastore.
- Proctoring signals must never block the student's request — always queue.
