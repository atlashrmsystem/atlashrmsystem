import http from 'k6/http';
import { check, sleep } from 'k6';

// Usage:
// BASE_URL=https://api.example.com AUTH_TOKEN=xxx k6 run scripts/k6-sync.js
//
// Notes:
// - Keep payload sizes within the documented budgets (≤1 MB / ≤1,000 records per batch).
// - This is a smoke/load hybrid; adjust VUs and duration per milestone.

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000/api';
const AUTH = __ENV.AUTH_TOKEN || '';
const LOCATION_ID = __ENV.LOCATION_ID || '1';

export const options = {
  vus: Number(__ENV.VUS || '25'),
  duration: __ENV.DURATION || '2m',
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    http_req_failed: ['rate<0.01'],
  },
};

export default function () {
  const headers = {
    Authorization: `Bearer ${AUTH}`,
    'Content-Type': 'application/json',
  };

  // Pull
  const pullPayload = JSON.stringify({
    client_id: 'K6-CLIENT',
    last_sync_at: __ENV.LAST_SYNC_AT || null,
    scope: { location_id: Number(LOCATION_ID) },
  });
  const pullRes = http.post(`${BASE_URL}/sync/pull`, pullPayload, { headers });
  check(pullRes, {
    'pull status 200': (r) => r.status === 200,
    'pull has server_time': (r) => r.json('server_time') !== undefined,
  });

  // Push (sales + stock_adjustments minimal)
  const pushPayload = JSON.stringify({
    client_id: 'K6-CLIENT',
    batch: {
      sales: [
        {
          client_txn_id: `SALE-${__VU}-${Date.now()}`,
          payload: {
            store_id: Number(__ENV.STORE_ID || '1'),
            date: new Date().toISOString().slice(0, 10),
            amount: 10.5,
          },
        },
      ],
      stock_adjustments: [
        {
          client_txn_id: `STK-${__VU}-${Date.now()}`,
          payload: {
            product_id: Number(__ENV.PRODUCT_ID || '1'),
            location_id: Number(LOCATION_ID),
            delta: 1,
          },
        },
      ],
      attendance: [],
    },
  });
  const pushRes = http.post(`${BASE_URL}/sync/push`, pushPayload, { headers });
  check(pushRes, {
    'push status 200': (r) => r.status === 200,
    'push accepted array': (r) => Array.isArray(r.json('accepted')),
  });

  sleep(Number(__ENV.SLEEP || '1'));
}
