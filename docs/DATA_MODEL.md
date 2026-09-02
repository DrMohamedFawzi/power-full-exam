# Aegis-X — Data Model

Migrations in `database/migrations/` are the source of truth. This is the map.

## Entities

```
institutions ──< users ──< user_devices ──< device_fingerprints
                  │  │
                  │  └──< classrooms ──< enrollment_requests >── users (student)
                  │            │
                  │            └──< exams ──< questions
                  │                   │
                  └───────────────────┴──< exam_sessions ──< exam_answers >── questions
                                              │
                                              ├──< violations
                                              ├──< heartbeats
                                              └──< keystroke_samples

threats >── users        banned_ips >── users (banned_by)
```

## Tables

| Table | Owner module | Notes |
|---|---|---|
| `institutions` | Identity | Tenant root. Teachers and students belong to one. |
| `users` | Identity | `role` ∈ student/teacher/institution. Teachers start `is_approved = false`. `qr_token` backs QR login. |
| `user_devices` | Identity | A student's bound devices. Unique per `(user_id, device_hash)`. |
| `device_fingerprints` | Identity | Fingerprint snapshots per device; `is_headless` flags automation. |
| `classrooms` | Academics | Owned by a teacher. `code` is the join code. |
| `enrollment_requests` | Academics | Unique per `(student, classroom)`. pending → approved/rejected. |
| `exams` | Assessment | `security_level` ∈ off/moderate/strict. `mode` ∈ official/mock_teacher/mock_student. `status` ∈ draft/published/closed. |
| `questions` | Assessment | Normalised out of the legacy `exams.questions_json` blob. Ordered by `position`. |
| `exam_sessions` | Assessment | One student's attempt. Carries `shuffle_seed`, `score`, `integrity_index`. |
| `exam_answers` | Assessment | One row per answered question; graded on submit. |
| `violations` | Proctoring | Typed + severity-scored proctoring events. Drives the integrity index. |
| `heartbeats` | Proctoring | Connectivity samples; accumulates `exam_sessions.offline_seconds`. |
| `keystroke_samples` | Proctoring | Typing-rhythm statistics; `anomaly_score` flags impersonation. |
| `threats` | Overwatch | WAF detections: attack type, payload, source IP. |
| `banned_ips` | Overwatch | Active bans, manual or automatic, with expiry. |

## Rules

- **Integrity index is derived.** Recomputed from a session's full violation set by
  `Proctoring\Actions\RecalculateIntegrityIndex`. Never incremented in place.
- **Answers are graded server-side only.** Correct answers never reach the browser before
  submission.
- **Shuffle is seeded** per session (`shuffle_seed`), so question order is reproducible when
  reviewing a submission or investigating a violation.
- Every foreign key is declared with an explicit delete behaviour: cascade for owned data,
  `nullOnDelete` for references that should survive the parent.

## Legacy mapping

| Legacy | Now |
|---|---|
| `users.institution_id` → users.id | `institutions` table, real FK |
| `classes` | `classrooms` (`class` is a reserved word and a confusing model name) |
| `exams.questions_json` | `questions` rows |
| `exam_sessions.current_seed` | `exam_sessions.shuffle_seed` |
| `fingerprints.student_id` | `device_fingerprints.user_device_id` (via `user_devices`) |
| `logs/*.json` file fallback | dropped |
