# Aegis-X — Architecture

> This document is **binding**. Any code that contradicts it is a bug.
> Read this before touching anything under `app/` or `resources/views/`.

## 1. What this system is

Aegis-X is an online-examination platform with integrity enforcement. Three human roles
(student, teacher, institution) plus an automated proctoring pipeline. Arabic-first, RTL.

The flow it exists to serve:

```
institution approves teacher
        └─ teacher creates class ──> student requests enrollment ──> teacher approves
                └─ teacher creates exam (manual or AI-generated)
                        └─ student sits exam in a locked-down runner
                                └─ client proctor emits signals ──> queue ──> integrity index
                                        └─ teacher/institution read results + violations
```

## 2. Architectural style: modular monolith

One Laravel application, partitioned into **bounded-context modules**. Modules are
directories, not packages — no separate composer packages, no service providers per module
beyond the single `ModuleServiceProvider`.

```
app/
├── Modules/
│   ├── Identity/     users, auth, sessions, QR login, trusted devices, fingerprints
│   ├── Academics/    institutions, teacher approval, classes, enrollment requests
│   ├── Assessment/   exams, questions, exam sessions, grading, AI generation
│   ├── Proctoring/   violations, heartbeats, integrity scoring, async pipeline
│   ├── Overwatch/    WAF, rate limiting, threat log, IP bans, security console
│   └── Dashboard/    role landing pages — read-only composition across the above
└── Support/          cross-cutting only: base classes, shared casts, view components
```

### Module dependency rule

A module may depend **only on modules below it** in this list. Downward references only —
no cycles, ever.

```
Dashboard    (may use: everything; owns no data, Queries and views only)
Overwatch    (may use: Identity)
Proctoring   (may use: Assessment, Academics, Identity)
Assessment   (may use: Academics, Identity)
Academics    (may use: Identity)
Identity     (may use: nothing)
Support      (used by all; depends on none)
```

If you need an upward reference, you have the boundary wrong — raise it rather than
importing sideways.

**`Dashboard` is the sanctioned exception**, and it earns it by being read-only: it holds
Queries and Blade views, no models, no writes, no migrations. A dashboard reads across
every context by nature, so instead of scattering that coupling — or letting `Identity`
learn about exams so it can render a landing page — the coupling is concentrated in one
module that everything else stays ignorant of.

### Talking upward: events

When a lower module needs to *inform* a higher one, it must not import it. Use an event.

The worked example is brute-force detection. `Overwatch` wants to know about failed logins,
but `Identity` may depend on nothing, so `Identity` cannot call `Overwatch`. Instead
`Identity` simply fails a login — Laravel fires `Illuminate\Auth\Events\Failed` — and
`Overwatch` listens for it. `Identity` has no idea `Overwatch` exists.

Declare listeners in `app/Modules/{Module}/events.php`, returning `[Event::class =>
[Listener::class]]`. `ModuleServiceProvider` registers them automatically.

### Module conventions

A module registers itself entirely by convention — nothing to wire up by hand:

| Path | Loaded as |
|---|---|
| `routes.php` or `Routes/*.php` | HTTP routes, under the `web` middleware group |
| `Policies/*Policy.php` | Authorization, matched to the same-named model |
| `Console/Commands/*.php` | Artisan commands |
| `Console/schedule.php` | Scheduled tasks |
| `events.php` | Event listeners |

### Anatomy of a module

```
app/Modules/Assessment/
├── Actions/            One write operation each. Invokable. This is where logic lives.
├── Queries/            Read side. Returns arrays/DTOs shaped for a specific view.
├── Data/               Readonly DTOs crossing layer boundaries.
├── Enums/              Backed enums for every status/type column.
├── Models/             Eloquent. Relations, casts, scopes. NOTHING ELSE.
├── Policies/           All authorization. No role checks anywhere else.
├── Http/
│   ├── Controllers/    Thin. Resolve → delegate → respond.
│   └── Requests/       All validation. Controllers never call $request->validate().
├── Jobs/               Queued work.
├── Services/           External-system adapters only (HTTP APIs, etc).
└── routes.php          The module's own routes. Auto-loaded.
```

## 3. The rules

These are the ones that get violated. They are not negotiable.

1. **Controllers are thin.** Resolve input from a Form Request, call one Action or Query,
   return a response. Hard ceiling: 60 lines per controller, 15 per method. No `if` on
   business conditions, no Eloquent queries, no `DB::`.
2. **Actions hold the writes.** One public `__invoke()` (or one intent-named method).
   An Action owns its transaction. Actions may call other Actions.
3. **Queries hold the reads.** Anything a dashboard renders comes from a Query class that
   returns exactly the shape the view needs. No Eloquent in Blade, ever — not even
   `$exam->questions->count()`.
4. **Models are dumb.** Relations, casts, scopes, accessors. No business methods, no
   static creators, no side effects.
5. **Policies own authorization.** No `if ($user->role === 'teacher')` outside a Policy or
   a route middleware. Roles are an enum, never a string literal.
6. **Form Requests own validation.** Including authorization delegation via `authorize()`.
7. **Every status column is a backed enum** with a `label()` (Arabic) and a `color()`
   (DaisyUI token) method. No magic strings crossing the codebase.
8. **No raw SQL string interpolation.** Bound parameters only. The legacy app concatenated
   user input into queries; that class of bug does not come back.
9. **No secrets in code or in tracked files.** `config/aegis.php` reads from `env()`.
   Application code reads `config()`, never `env()` outside `config/`.
10. **Blade files are markup.** No logic beyond `@if`/`@foreach` over prepared data. Every
    repeated visual element is a component in `resources/views/components/`.
11. **File size ceiling: 350 lines.** Blade, PHP, JS alike. Over that, decompose.
12. **Proctoring signals never block a response.** They queue.

## 4. Request lifecycle

```
Request
  → global middleware (Overwatch\Http\Middleware\ShieldRequest: ban check, rate limit, WAF)
  → route middleware (auth, role gate, exam-session gate)
  → FormRequest        validation + authorize() → Policy
  → Controller         thin
  → Action / Query     the actual work
  → Model / external
  → Blade view (components) or JsonResponse
```

## 5. Frontend

Blade + Tailwind 4 + DaisyUI + Alpine.js. No SPA, no build-time framework.

- **Layouts**: `resources/views/layouts/{app,auth,exam}.blade.php`.
- **Components**: `resources/views/components/ui/*` (presentational, dumb) and
  `components/{module}/*` (domain-shaped, still presentational).
- **Interactivity**: Alpine, declared inline on the element. Anything past ~20 lines of
  behaviour becomes an Alpine component registered in `resources/js/`.
- **RTL is the default**, not an override. `<html dir="rtl" lang="ar">`. Use logical
  properties (`ms-*`, `me-*`, `ps-*`, `pe-*`) — never `ml-*`/`mr-*`.
- **Design tokens** live in the DaisyUI theme in `resources/css/app.css`. No hex colours in
  Blade or JS. Semantic tokens only: `primary`, `success`, `warning`, `error`, `base-*`.

Full visual contract: [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md).

## 6. The proctoring pipeline

The client-side proctor (`resources/js/proctoring/`) observes the exam window and emits
signals. Signals are cheap to send and never block the student:

```
browser proctor ──POST /exam/{session}/signal──> IngestSignalController
                                                       └─ dispatch(ProcessProctoringSignal)
                                                              ├─ ClassifyViolation  (type + severity)
                                                              ├─ RecordViolation    (persist)
                                                              └─ RecalculateIntegrityIndex
```

The legacy Python worker + RabbitMQ are gone; this is Laravel queue work now
(`QUEUE_CONNECTION=database`, `php artisan queue:work`). Keystroke-dynamics analysis, which
was the one genuinely analytical piece of the Python service, is ported to
`Proctoring/Actions/AnalyzeKeystrokeDynamics`.

**Integrity index** is derived, never incremented ad hoc: it is recomputed from the full
violation set of a session by `RecalculateIntegrityIndex`, so it is always reproducible.

## 7. Authentication

Laravel session auth, not the legacy hand-rolled JWT. Same-origin Blade app — sessions plus
CSRF are strictly stronger than a hand-written HS256 implementation, and the JWT bought
nothing since there was never a separate client.

- Password login, and **QR login** (a signed, single-use, short-TTL token encoded in a QR).
- **Trusted devices**: a student binds a device fingerprint on first exam; unrecognised
  devices are blocked or flagged per exam `security_level`.
- Teachers require institution approval (`is_approved`) before they can act.

## 8. Data model

See [DATA_MODEL.md](DATA_MODEL.md). Migrations are the source of truth. Notable departures
from legacy:

- Schema lives in migrations only. The legacy `CREATE TABLE IF NOT EXISTS` + `try { ALTER
  TABLE } catch {}` block that ran on **every single API request** is gone.
- Foreign keys and indexes are declared and enforced.
- `exams.questions_json` is normalised into a `questions` table.
- The JSON-file "fallback database" under `logs/` is gone. One datastore.

## 9. What was deliberately dropped

| Legacy | Why |
|---|---|
| `api.php` (2,964 lines, 35 `if ($action === …)` branches) | Replaced by module routes + controllers |
| Hand-rolled JWT | Session auth |
| RabbitMQ + `proctoring_service.py` | Laravel queue jobs |
| JSON-file DB fallback (`logs/*.json`) | Silent divergence from real data |
| Per-request schema migration | Migrations |
| Hand-rolled `AntigravityRedis` / `AntigravityQueue` | Laravel cache + queue |
| PgSQL wrapper classes emulating mysqli | Eloquent / PDO |
| iOS user-agent block | UA sniffing is trivially bypassed; device binding does the real work |
| `config.json` with committed secrets | `.env` |
| `VisionMonitorPlugin.js` identity verification (face descriptor/mesh matching, luminance, velocity strikes) | Out of scope, not dropped as tech debt — see §10 |

## 10. Vision proctoring: identity verification is out of scope

Legacy shipped `_legacy/core/security/VisionMonitorPlugin.js`, a ~660-line, per-frame
camera pipeline built on Google MediaPipe (`face_detection` + `face_mesh` WASM, bundled
locally) and `face-api.js`. Its scope was much larger than "is a face present":

- **Enrollment-photo identity matching.** On init it loaded the student's
  `profile_picture`, computed a 128-d face descriptor with `face-api.js`, and separately
  extracted a set of normalized 3D landmark ratios (nose-to-chin, mouth width,
  eye-to-nose, relative to inter-eye distance) from MediaPipe Face Mesh's 468 landmarks.
- **Calibration-time gate.** Before an exam with `security_level = strict` could start,
  the live webcam face had to match the enrollment photo (`euclideanDistance < 0.65`, or
  a mesh-ratio score `≥ 70%`) or calibration was rejected outright and the exam never
  started. Accuracy was blended from both signals into a single displayed percentage.
- **Periodic re-verification.** Every 15s during the exam, the same descriptor match ran
  again; two consecutive mismatches (or three consecutive no-face frames) raised an
  impersonation strike (`"انتحال شخصية"` — "identity impersonation").
- **Safe-zone + velocity tracking.** A bounding-box baseline captured during a ~1.5s
  calibration window, expanded 15%, then used as a rubber-band tolerance zone; frame-to-
  frame velocity `> 0.16` (normalized) flagged sudden motion (phone-grab / fast turn).
- **Luminance-anomaly detection.** Average face-region luminance was baselined at
  calibration; a sustained ±40%/60% swing over ~3s flagged a likely phone or second-screen
  glare, tracked and struck independently of the positional strikes (5-strike ceiling of
  its own).
- **Strike system with distinct fatal/warning events** (`VisionWarningEvent` /
  `VisionFatalViolationEvent`, `LuminanceWarningEvent` / `LuminanceFatalViolationEvent`),
  each capped at 5 before escalating to fatal.

**Decision: this identity-verification and richer CV layer is deliberately not carried
into the rewrite.** `resources/js/proctoring/vision.js` keeps only the presence-based
subset: `face_missing` (sustained no-face), `multiple_faces`, and a coarse `gaze_away`
proxy from bounding-box drift off frame-centre — no descriptor extraction, no mesh
ratios, no enrollment-photo comparison, no periodic re-verification, no luminance
tracking, no velocity-based motion strikes. There is no reference-face column anywhere
in `database/migrations/`; the `avatar_path` a user uploads is display-only and is never
read by any Action or the vision monitor.

**Why it was cut, not ported:**

- **External dependency the legacy app paid for silently.** MediaPipe's WASM/model
  binaries were vendored under `_legacy/core/security/mediapipe/` (multiple `.wasm` /
  `.data` blobs); `face-api.js`'s own model weights were loaded separately. Both this and
  the rewrite's model instead lazy-load from a CDN (`storage.googleapis.com`,
  `cdn.jsdelivr.net`) — see the comment in `vision.js` — but a Laravel repo has no clean
  place to vendor multi-megabyte binary model assets, and the module boundary rules in
  §2 don't have a home for a client-side ML pipeline this large. Shipping it means
  either committing the blobs (bloats the repo, defeats `git` diffing) or depending on
  the same third-party CDNs, which is a live-availability risk for something that gates
  exam start.
- **False-positive cost is asymmetric and severe.** A rejected calibration or a fatal
  impersonation strike stops the student from taking or finishing the exam. The legacy
  thresholds (`distance < 0.65`, mesh score `≥ 70`) were tuned informally against no
  documented dataset; lighting, webcam quality, and camera angle all move the descriptor
  distance in production in ways a threshold picked by trial and error does not survive.
  Shipping a face-identity gate without a validated accuracy/false-reject rate is worse
  than not shipping one — it turns intermittent hardware/lighting variance into wrongly
  blocked exams.
- **No consent/retention story.** Enrollment-photo descriptors and periodic re-scans are
  biometric processing. Legacy never defined retention, consent capture, or a deletion
  path for `profileDescriptor` / `profileMeshRatios` (computed client-side each session,
  from a photo stored server-side indefinitely as `profile_picture`). Re-introducing this
  needs that policy decided first — it is a product/legal decision, not a coding task,
  and does not belong in `app/Modules/Proctoring` until it exists.
- **It duplicates rather than composes with the pipeline in §6.** Both the face-mesh
  identity check and the luminance/velocity strikes ran their own independent strike
  counters, disconnected from `ClassifyViolation` / `RecordViolation` /
  `RecalculateIntegrityIndex`. Carrying it forward as-is would mean either a second,
  parallel scoring system or a nontrivial redesign to fold biometric confidence into one
  `IntegrityIndex` — the redesign is the right call, but it is unscoped work, not a
  straight port.

**What would be needed to bring it back in scope** (tracked here, not started):

1. A product decision on whether facial identity verification is required for
   `security_level = strict` exams, including a documented consent flow and retention
   policy for any stored descriptor/photo.
2. A `reference_face` concept distinct from the cosmetic `avatar_path` — its own
   migration, its own upload/consent Action, not reusing profile-picture upload.
3. A `FaceIdentity`-style Action pair (`ComputeReferenceDescriptor`,
   `VerifyLiveFace`) in `Proctoring/Actions/`, decided against a real accuracy budget
   rather than a copied threshold.
4. New `ViolationType` cases (e.g. `IdentityMismatch`) wired through the existing
   `ClassifyViolation` → `RecordViolation` → `RecalculateIntegrityIndex` pipeline in §6,
   instead of a second strike system.
5. A vendoring decision for the ML model assets (commit them vs. CDN-with-fallback vs. a
   server-side verification service) made explicitly, not inherited by default.

Until all five exist, `vision.js`'s presence/gaze-only behaviour is the intended
implementation, not an in-progress gap.

## 11. Conventions

- PHP 8.4, `declare(strict_types=1)` in every file, constructor property promotion,
  readonly DTOs, typed properties and returns everywhere.
- Actions: imperative verb — `CreateExam`, `ApproveEnrollment`, `RevokeDevice`.
- Queries: `…Query` — `TeacherDashboardQuery`, `StudentResultsQuery`.
- Tests: PHPUnit (Laravel's default), in `tests/Feature/{Module}/`. Feature tests per route,
  unit tests for Actions with non-trivial logic. They run against in-memory SQLite via
  `phpunit.xml`, while the app itself runs MySQL — so anything relying on MySQL-specific
  behaviour needs a test that says so explicitly.
- Migrations are additive after first release; never edit a shipped migration.

## 12. Edge Cases, Security Hardening & Resilience Contracts

### 12.1 Thundering Herd Protection (9:00:00 AM Rush)
- **Client Jitter**: Frontend adds 0–15s randomized delay (`Math.random() * 15000`) before calling session start endpoints when opening exam runners concurrently.
- **Pre-fetching & Local Unlock**: Questions are pre-downloaded 5 minutes prior to exam start (`opens_at - 5m`), stored encrypted in `IndexedDB`. At `opens_at`, the local runner unlocks without issuing network requests.
- **Virtual Waiting Room**: When incoming request velocity exceeds server capacity (>1,000 RPS), Redis rate limiter places requests in a queued batch state with a countdown (`HTTP 202` / Redis Gate Keeper).

### 12.2 Memory Scraping & Just-In-Time (JIT) Decryption
- **JIT Question Decryption**: Only the active question currently rendered on screen is decrypted in JS RAM; remaining questions stay AES-256 encrypted.
- **Heap Scrubbing**: Navigating away from a question overwrites its plaintext JS object with random bytes before invoking garbage collection.
- **Canvas Rendering**: Multimedia assets (diagrams, medical imagery) are rendered directly to HTML5 Canvas (`ctx.drawImage()`) with dynamic watermarks (`student_id + IP`), leaving no Base64/Blob `src` in the DOM tree.

### 12.3 Split-Brain & Offline Conflict Resolution
- **Single Active Session Enforcement**: Logging in from a second device revokes the previous JWT token and increments `session_version`.
- **Vector Clock Merge**: Offline submissions stored in `IndexedDB` carry CPU monotonic timestamps (`performance.now()`). Server merges non-overlapping answers and resolves conflicts by selecting the newest valid monotonic timestamp while logging a `concurrent_device_flag` violation.

### 12.4 Graceful Degradation for Low-End Devices (OOM Prevention)
- **Web Worker Offloading**: `vision.js` and decryption run inside background Web Workers, keeping main UI thread at 60 FPS.
- **Dynamic FPS Scaling**: If device frame rate drops below 15 FPS, vision proctoring automatically scales down camera resolution (`320x240`) and increases check intervals (from 3s to 10s) to prevent browser OOM crashes on low-memory hardware (<3GB RAM).

