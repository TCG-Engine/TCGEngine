// k6 load test runner for SWU Stats endpoints.
//
// Run:  k6 run SWUSim/Tests/LoadTests/loadtest.js
//
// All knobs are env vars (override with -e KEY=value):
//   BASE_URL   base origin to hit                 (default: http://localhost:3100)
//   PATH       request path under BASE_URL        (default: the static APIs docs page)
//   RATE       requests per second                (default: 50)
//   DURATION   how long to sustain RATE           (default: 10s)
//   VUS        preallocated virtual users         (default: 200)
//   MAX_VUS    ceiling on virtual users           (default: 1000)
//
// IMPORTANT — do not point this at APIs/MeleeLinkToJson.php (or any endpoint that
// proxies to melee.gg). That endpoint is UNCACHED and makes one live fetch to
// melee.gg per request, so a load run becomes a DoS against a third party and will
// likely get the server IP blocked there. Load-test your own cacheable endpoints.
//
// Before aiming high RATE at live prod (swustats.net): coordinate it, run it off-peak,
// and ramp up — start low, watch error rate and p95, then increase. A sustained
// 1000 rps against production is a capacity test, not a smoke test.

import http from 'k6/http';
import { check } from 'k6';
import { Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:3100';
const PATH     = __ENV.PATH     || '/TCGEngine/Stats/APIs.php';
const RATE     = parseInt(__ENV.RATE     || '50', 10);
const DURATION = __ENV.DURATION || '10s';
const VUS      = parseInt(__ENV.VUS      || '200', 10);
const MAX_VUS  = parseInt(__ENV.MAX_VUS  || '1000', 10);

const errorRate = new Rate('errors');

export const options = {
  scenarios: {
    constant_load: {
      executor: 'constant-arrival-rate',
      rate: RATE,
      timeUnit: '1s',
      duration: DURATION,
      preAllocatedVUs: VUS,
      maxVUs: MAX_VUS,
    },
  },
  thresholds: {
    // Fail the run if more than 1% error or p95 latency blows past 2s.
    errors: ['rate<0.01'],
    http_req_duration: ['p(95)<2000'],
  },
};

export default function () {
  const res = http.get(`${BASE_URL}${PATH}`);
  const ok = check(res, {
    'status is 2xx': (r) => r.status >= 200 && r.status < 300,
  });
  errorRate.add(!ok);
}
