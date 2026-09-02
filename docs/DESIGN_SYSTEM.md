# Aegis-X — Design System

The visual contract. If a screen needs something not described here, add it here first,
as a component, then use it.

## Principles

1. **Calm by default, loud only for integrity.** The interface is quiet greys and one blue.
   Red and amber are reserved for proctoring signals and security events — spend them there
   and nowhere else, or they stop meaning anything.
2. **Arabic first.** RTL is the base direction, not a flip applied afterwards. Cairo, 400–800.
3. **One surface language.** Content sits on `.surface` cards over a `base-200` canvas.
   No nested cards, no card-in-card.
4. **Density suits the role.** Students see large, unambiguous targets under exam stress;
   teachers and institutions see denser tables.

## Tokens

Defined once, in `resources/css/app.css`, as two DaisyUI themes: `aegis` (light) and
`aegis-dark`. Never write a colour anywhere else.

| Token | Use |
|---|---|
| `primary` | Primary actions, active nav, links |
| `secondary` | Institution-scoped surfaces |
| `accent` | AI-generated content markers |
| `success` | Passing scores, healthy integrity (≥ 85), approvals |
| `warning` | Integrity 60–84, pending states, low-severity violations |
| `error` | Integrity < 60, critical violations, bans, destructive actions |
| `base-100` | Card surfaces | 
| `base-200` | Page canvas |
| `base-300` | Borders, dividers |

Radii: `rounded-box` (cards) · `rounded-field` (inputs) · `rounded-full` (avatars, pills).
Shadows: `shadow-sm` for resting cards, `shadow-lg` for overlays. Nothing heavier.

## Typography

| Role | Class |
|---|---|
| Page title | `text-2xl lg:text-3xl font-extrabold` |
| Section title | `.section-title` |
| Body | inherited, `text-base` |
| Secondary | `.muted` |
| Numbers, codes, timers | `.numeric` — tabular figures, isolated LTR |

Never let a bare number sit in an Arabic sentence without `.numeric`; the bidi algorithm
will reorder it.

## Components

Primitives in `resources/views/components/ui/`. Compose, don't re-invent.

| Component | Purpose |
|---|---|
| `<x-ui.card title icon>` with `<x-slot:actions>` / `<x-slot:footer>` | Every content block |
| `<x-ui.stat label value icon color trend>` | KPI tiles — max 4 per row |
| `<x-ui.badge color icon>` | Status. Colour always comes from the enum's `color()` |
| `<x-ui.input>` `<x-ui.select>` `<x-ui.textarea>` | Form fields; wire errors automatically |
| `<x-ui.modal id title>` | Confirmations and short forms |
| `<x-ui.page-header title description :breadcrumbs>` | Top of every page |
| `<x-ui.empty-state icon title description>` | Every list, when empty |
| `<x-ui.integrity-meter :value>` | The integrity index, everywhere it appears |
| `<x-ui.flash>` | Server-side messages; already in the layouts |
| `<x-ui.toast-host>` | Client-side messages via `window.aegis.toast()` |

Buttons use DaisyUI directly: `btn btn-primary`, `btn btn-ghost`, `btn btn-sm`. Destructive
buttons are `btn-error` and always confirm first.

## Layouts

| Layout | For |
|---|---|
| `layouts.app` | Authenticated pages: sidebar + topbar + footer |
| `layouts.guest` | Landing, login, register, QR login |
| `layouts.exam` | The exam runner. No nav, no outbound links, nothing to click but the exam |

## Integrity colour scale

One scale, used identically in every surface that shows it:

| Index | Tone | Arabic label |
|---|---|---|
| 85–100 | `success` | ممتاز |
| 60–84 | `warning` | مقبول |
| 0–59 | `error` | مشبوه |

Encoded in `<x-ui.integrity-meter>`. Don't re-derive it inline.

## Data display

- Tables: `table table-zebra` inside `<div class="overflow-x-auto">`. Numeric columns get
  `.numeric` and `text-start`. Row actions live in the last column.
- Charts: Chart.js, colours pulled from CSS custom properties so they follow the theme —
  never hard-coded. Always pair a chart with the underlying number in text.
- Long lists paginate at 20. Empty states are mandatory.

## Motion

`.animate-in` for content entering, 150–350 ms, ease-out. Loading uses `.shimmer`
skeletons shaped like the content, not spinners. `prefers-reduced-motion` is honoured
globally — don't bypass it.

## Accessibility

- Contrast ≥ 4.5:1; the two themes are built to satisfy this.
- Every icon-only control needs `aria-label`. Every input needs a real `<label>`.
- Focus is visible everywhere (`:focus-visible` is styled globally) — never `outline-none`.
- Status is never conveyed by colour alone: pair every coloured badge with text.
- Modals are `<dialog>`, so focus trapping and Escape come for free.
