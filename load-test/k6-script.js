/**
 * Aegis-X — K6 Load Test Script
 * ===========================================
 * Target:   50,000 concurrent virtual users
 * Goal:     p(95) latency < 200ms  |  Error Rate = 0%
 *
 * ─── الاستخدام — مراحل تصاعدية (ابدأ دائماً من الأدنى!) ───────────
 *
 * المرحلة 1 — تأكيد الصحة (500 VU):
 *   k6 run load-test/k6-script.js \
 *     --env BASE_URL=http://localhost:8000 \
 *     --env EXAM_ID=1 \
 *     --env STUDENT_PREFIX=student \
 *     --env STUDENT_PASS=password123 \
 *     --env STAGE_TARGET=500
 *
 * المرحلة 2 — اختبار الضغط (5,000 VU):
 *     --env STAGE_TARGET=5000
 *
 * المرحلة 3 — الحد الأقصى على localhost (10,000–20,000 VU):
 *     --env STAGE_TARGET=20000
 *
 * المرحلة 4 — الاختبار الكامل 50,000 (يحتاج k6 Cloud أو EC2):
 *     --env STAGE_TARGET=50000
 *
 * Routes المُستخدمة:
 *   POST /api/auth/token              → إصدار JWT (TokenController)
 *   POST /api/exam-sessions/{exam}    → بدء جلسة
 *   GET  /api/exam-sessions/{session} → تحميل الأسئلة
 *   POST /api/exam-sessions/{session}/answer  → حفظ إجابة
 *   POST /api/exam-sessions/{session}/submit  → تسليم الامتحان
 *   POST /api/proctoring/heartbeat    → نبضة قلب
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { SharedArray } from 'k6/data';

// ── Configuration ─────────────────────────────────────────────────
const BASE_URL       = __ENV.BASE_URL       || 'http://localhost:8000';
const EXAM_ID        = __ENV.EXAM_ID        || '1';
// كل VU يختار طالباً مختلفاً: student1@aegis-x.test, student2@...
const STUDENT_PREFIX = __ENV.STUDENT_PREFIX || 'student';
const STUDENT_PASS   = __ENV.STUDENT_PASS   || 'password123';
const TOTAL_STUDENTS = parseInt(__ENV.TOTAL_STUDENTS || '50000');
// الهدف من الـ VUs (قابل للتعديل بدون تغيير الكود)
const STAGE_TARGET   = parseInt(__ENV.STAGE_TARGET || '500');

// ── Custom Metrics ─────────────────────────────────────────────────
const errorRate       = new Rate('exam_error_rate');
const autosaveLatency = new Trend('autosave_latency_ms', true);
const heartbeatLatency= new Trend('heartbeat_latency_ms', true);
const tokenLatency    = new Trend('token_issue_latency_ms', true);

// ── Thresholds (acceptance criteria) ──────────────────────────────
export const options = {
  scenarios: {
    exam_rush: {
      executor: 'ramping-vus',
      stages: [
        { duration: '2m',  target: Math.ceil(STAGE_TARGET * 0.1) },  // warm-up 10%
        { duration: '3m',  target: Math.ceil(STAGE_TARGET * 0.5) },  // ramp-up 50%
        { duration: '5m',  target: STAGE_TARGET },                    // sustained 100%
        { duration: '2m',  target: 0 },                               // ramp-down
      ],
      gracefulRampDown: '60s',
    },
  },

  thresholds: {
    'http_req_duration': [
      'p(50) < 200',    // Median < 200ms
      'p(95) < 500',    // 95th percentile < 500ms (على localhost أكثر تساهلاً)
      'p(99) < 1000',
    ],
    'exam_error_rate':     ['rate < 0.01'],  // < 1% errors مقبولة على localhost
    'autosave_latency_ms': ['p(95) < 200'],
    'heartbeat_latency_ms':['p(95) < 100'],
    'http_req_failed':     ['rate < 0.01'],
  },
};

// ── Shared question IDs ────────────────────────────────────────────
const questionIds = new SharedArray('questionIds', () => {
  return Array.from({ length: 10 }, (_, i) => i + 1);
});

// ── Helper: اختيار طالب عشوائي ────────────────────────────────────
function getStudentCredentials() {
  // كل VU يختار رقماً عشوائياً من بين 50,000 طالب
  const n = Math.floor(Math.random() * TOTAL_STUDENTS) + 1;
  return {
    username: `${STUDENT_PREFIX}${n}`,
    password: STUDENT_PASS,
  };
}

// ── Main VU scenario ───────────────────────────────────────────────
export default function () {
  const creds     = getStudentCredentials();
  let   token     = null;
  let   sessionId = null;

  // ── 1. تسجيل الدخول والحصول على JWT ──────────────────────────────
  group('1. JWT Authentication', () => {
    const start = Date.now();

    const res = http.post(
      `${BASE_URL}/api/auth/token`,
      JSON.stringify({ username: creds.username, password: creds.password }),
      { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
    );

    tokenLatency.add(Date.now() - start);

    const ok = check(res, {
      'JWT issued (200)':  (r) => r.status === 200,
      'has access_token':  (r) => {
        try { return r.json('access_token') !== null; } catch { return false; }
      },
    });

    errorRate.add(!ok);
    if (!ok) return;
    token = res.json('access_token');
  });

  if (!token) { sleep(1); return; }

  const authHeaders = {
    'Authorization': `Bearer ${token}`,
    'Content-Type':  'application/json',
    'Accept':        'application/json',
  };

  // ── 2. بدء جلسة الامتحان ──────────────────────────────────────────
  group('2. Start Exam Session', () => {
    const res = http.post(
      `${BASE_URL}/api/exam-sessions/${EXAM_ID}`,
      JSON.stringify({ device_hash: `k6-vu-${__VU}-iter-${__ITER}` }),
      { headers: authHeaders, tags: { endpoint: 'session_start' } }
    );

    const ok = check(res, {
      'Session created (200/201)': (r) => r.status === 200 || r.status === 201,
      'has session_id': (r) => {
        try {
          const body = r.json();
          return body.session?.id != null || body.id != null;
        } catch { return false; }
      },
    });

    errorRate.add(!ok);
    if (!ok) return;

    try {
      const body = res.json();
      sessionId = body.session?.id ?? body.id;
    } catch { /* session ID extraction failed */ }
  });

  if (!sessionId) { sleep(1); return; }

  // ── 3. تحميل صفحة الامتحان ────────────────────────────────────────
  group('3. Load Exam Questions', () => {
    const res = http.get(
      `${BASE_URL}/api/exam-sessions/${sessionId}`,
      { headers: authHeaders, tags: { endpoint: 'session_show' } }
    );

    const ok = check(res, {
      'Questions loaded (200)': (r) => r.status === 200,
    });
    errorRate.add(!ok);
  });

  // ── 4. حفظ الإجابات تلقائياً (10 أسئلة) ─────────────────────────
  group('4. Autosave Answers', () => {
    for (let i = 0; i < 10; i++) {
      const qId   = questionIds[i % questionIds.length];
      const start = Date.now();

      const res = http.post(
        `${BASE_URL}/api/exam-sessions/${sessionId}/answer`,
        JSON.stringify({
          question_id:        qId,
          answer:             [String(Math.floor(Math.random() * 4))],
          time_spent_seconds: Math.floor(Math.random() * 60) + 10,
        }),
        { headers: authHeaders, tags: { endpoint: 'answer_save' } }
      );

      autosaveLatency.add(Date.now() - start);
      const ok = check(res, { 'Answer accepted (200)': (r) => r.status === 200 });
      errorRate.add(!ok);

      sleep(0.2); // 200ms بين الإجابات (واقعي)
    }
  });

  // ── 5. Heartbeats (3 نبضات) ──────────────────────────────────────
  group('5. Heartbeat', () => {
    for (let b = 0; b < 3; b++) {
      const start = Date.now();

      const res = http.post(
        `${BASE_URL}/api/proctoring/heartbeat`,
        JSON.stringify({ session_id: sessionId, status: 'online', ts: new Date().toISOString() }),
        { headers: authHeaders, tags: { endpoint: 'heartbeat' } }
      );

      heartbeatLatency.add(Date.now() - start);
      check(res, { 'Heartbeat OK (200/202)': (r) => r.status === 200 || r.status === 202 });
      sleep(1);
    }
  });

  // ── 6. تسليم الامتحان ────────────────────────────────────────────
  group('6. Submit Exam', () => {
    const res = http.post(
      `${BASE_URL}/api/exam-sessions/${sessionId}/submit`,
      JSON.stringify({}),
      { headers: authHeaders, tags: { endpoint: 'session_submit' } }
    );

    const ok = check(res, { 'Submitted (200/201)': (r) => r.status === 200 || r.status === 201 });
    errorRate.add(!ok);
  });

  sleep(0.5);
}

// ── Summary ────────────────────────────────────────────────────────
export function handleSummary(data) {
  const p95      = data.metrics['http_req_duration']?.values['p(95)'] ?? 9999;
  const errRate  = data.metrics['exam_error_rate']?.values?.rate ?? 1;
  const passed   = p95 < 500 && errRate < 0.01;

  console.log('\n╔═══════════════════════════════════════════════════╗');
  console.log('║         AEGIS-X LOAD TEST — النتائج               ║');
  console.log('╚═══════════════════════════════════════════════════╝');
  console.log(`  Peak VUs:           ${STAGE_TARGET.toLocaleString()}`);
  console.log(`  p(50) Latency:      ${(data.metrics['http_req_duration']?.values['p(50)'] ?? 0).toFixed(0)}ms`);
  console.log(`  p(95) Latency:      ${p95.toFixed(0)}ms  (target: < 500ms)`);
  console.log(`  p(99) Latency:      ${(data.metrics['http_req_duration']?.values['p(99)'] ?? 0).toFixed(0)}ms`);
  console.log(`  Error Rate:         ${(errRate * 100).toFixed(3)}%`);
  console.log(`  Autosave p(95):     ${(data.metrics['autosave_latency_ms']?.values['p(95)'] ?? 0).toFixed(0)}ms`);
  console.log(`  Heartbeat p(95):    ${(data.metrics['heartbeat_latency_ms']?.values['p(95)'] ?? 0).toFixed(0)}ms`);
  console.log(`  Token Issue p(95):  ${(data.metrics['token_issue_latency_ms']?.values['p(95)'] ?? 0).toFixed(0)}ms`);
  console.log(`  VERDICT:            ${passed ? '✅ PASSED' : '❌ FAILED — راجع الـ thresholds'}`);
  console.log('══════════════════════════════════════════════════════\n');

  // إنشاء مجلد النتائج تلقائياً
  return {
    'stdout': JSON.stringify(data.metrics, null, 2),
    'load-test/results/latest.json': JSON.stringify(data, null, 2),
  };
}
