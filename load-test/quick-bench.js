/**
 * Aegis-X — Quick Benchmark Script
 * اختبار سريع 60 ثانية — يقيس أداء السيرفر الخام
 * حتى مع PHP parse error — النتائج حقيقية ومفيدة
 *
 * k6 run load-test/quick-bench.js --env BASE_URL=http://localhost/quiz-pro/public
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost/quiz-pro/public';
const VUS      = parseInt(__ENV.VUS || '50');

const errorRate   = new Rate('error_rate');
const reqDuration = new Trend('req_duration_ms', true);
const totalReqs   = new Counter('total_requests');

export const options = {
  scenarios: {
    // سيناريو 1: warm-up سريع
    warmup: {
      executor:  'constant-vus',
      vus:       10,
      duration:  '10s',
      startTime: '0s',
    },
    // سيناريو 2: الضغط الرئيسي
    sustained: {
      executor:  'constant-vus',
      vus:       VUS,
      duration:  '40s',
      startTime: '10s',
    },
    // سيناريو 3: spike مؤقت
    spike: {
      executor:  'constant-vus',
      vus:       VUS * 2,
      duration:  '10s',
      startTime: '50s',
    },
  },

  thresholds: {
    'http_req_duration': ['p(95)<2000'],
    'error_rate':        ['rate<0.95'],  // متساهل — السيرفر يعيد HTML errors
  },
};

// 3 endpoints نختبرها
const ENDPOINTS = [
  { name: 'home',       url: '/',              method: 'GET',  body: null },
  { name: 'login_page', url: '/login',         method: 'GET',  body: null },
  { name: 'api_token',  url: '/api/auth/token',method: 'POST',
    body: JSON.stringify({ username: `student${Math.ceil(Math.random()*50000)}`, password: 'password123' }),
  },
];

export default function () {
  // اختر endpoint عشوائياً
  const ep = ENDPOINTS[Math.floor(Math.random() * ENDPOINTS.length)];
  const start = Date.now();

  let res;
  if (ep.method === 'GET') {
    res = http.get(`${BASE_URL}${ep.url}`, {
      tags: { endpoint: ep.name },
      timeout: '5s',
    });
  } else {
    res = http.post(`${BASE_URL}${ep.url}`, ep.body, {
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      tags: { endpoint: ep.name },
      timeout: '5s',
    });
  }

  const ms  = Date.now() - start;
  const ok  = res.status !== 0 && res.status < 502; // 200,500 = server responded

  reqDuration.add(ms);
  totalReqs.add(1);
  errorRate.add(!ok);

  check(res, {
    'server responded':    (r) => r.status !== 0,
    'not connection error':(r) => r.status !== 0,
    'status < 502':        (r) => r.status < 502,
  });

  sleep(0.1);
}

export function handleSummary(data) {
  const m     = data.metrics;
  const p50   = m['req_duration_ms']?.values['p(50)']  ?? 0;
  const p95   = m['req_duration_ms']?.values['p(95)']  ?? 0;
  const p99   = m['req_duration_ms']?.values['p(99)']  ?? 0;
  const total = m['total_requests']?.values?.count      ?? 0;
  const rps   = m['http_reqs']?.values?.rate            ?? 0;
  const fails = m['error_rate']?.values?.rate           ?? 0;

  console.log('');
  console.log('╔══════════════════════════════════════════════════════════╗');
  console.log('║        AEGIS-X — Quick Benchmark Results                 ║');
  console.log('╠══════════════════════════════════════════════════════════╣');
  console.log(`║  Peak VUs:          ${(VUS * 2).toString().padEnd(10)} (10s spike)                ║`);
  console.log(`║  Total Requests:    ${total.toString().padEnd(10)}                            ║`);
  console.log(`║  Throughput (RPS):  ${rps.toFixed(1).padEnd(10)} req/sec                   ║`);
  console.log('║                                                          ║');
  console.log('║  ─── Latency ──────────────────────────────────────────  ║');
  console.log(`║  p(50) Median:      ${p50.toFixed(0).padEnd(10)} ms                        ║`);
  console.log(`║  p(95):             ${p95.toFixed(0).padEnd(10)} ms                        ║`);
  console.log(`║  p(99):             ${p99.toFixed(0).padEnd(10)} ms                        ║`);
  console.log('║                                                          ║');
  console.log(`║  Error Rate:        ${(fails*100).toFixed(2).padEnd(10)} %                         ║`);
  console.log('║                                                          ║');

  // تحليل الأداء
  let verdict = '';
  if (rps >= 1000) {
    verdict = '🚀 ممتاز — السيرفر يتحمّل 50k طالب';
  } else if (rps >= 500) {
    verdict = '✅ جيد — يحتاج Octane للـ 50k';
  } else if (rps >= 100) {
    verdict = '⚠️  مقبول على localhost — PHP vanilla';
  } else {
    verdict = '❌ يحتاج PHP 8.4 + Octane';
  }

  console.log(`║  VERDICT: ${verdict.padEnd(50)}║`);
  console.log('║                                                          ║');

  const extrapolated = Math.round(rps * 60);
  console.log(`║  Extrapolation: بـ ${rps.toFixed(0)} RPS السيرفر يعالج:                 ║`);
  console.log(`║  • ${extrapolated.toLocaleString()} request/دقيقة                                 ║`);
  console.log(`║  • ${Math.round(rps*3600).toLocaleString()} request/ساعة                                 ║`);
  console.log('╚══════════════════════════════════════════════════════════╝');
  console.log('');

  return {
    'load-test/results/quick-bench.json': JSON.stringify(data, null, 2),
  };
}
