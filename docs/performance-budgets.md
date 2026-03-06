# Performance Budgets & Observability (2026-03-06)

## Budgets
- `/api/sync/push`: p95 < 2s at 100 concurrent; batch size ≤ 1 MB and ≤ 1,000 records.
- `/api/sync/pull`: p95 < 2s for up to 10k rows paged (≤ 1,000 rows per page); cursor token required.
- Worker queue latency: p95 < 30s from enqueue to completion.
- POS online checkout (local → API) p95 < 5s.
- Error rates: HTTP 5xx < 1% per 5-minute window.

## Test Approach
- Load: k6 script (`scripts/k6-sync.js`) parameterized by `BASE_URL` and `AUTH_TOKEN`.
- Data volume: use staged fixtures sized to mimic a busy store (10k products, 50k stock rows, 5k daily transactions).
- Run cadence: per milestone (A0/B/C), pre-release, and after significant schema/index changes.

## Observability Standard
- Structured JSON logs containing `correlation_id`, `client_id`, `client_txn_id`, `scope.location_id`.
- Metrics: sync success/fail counts, conflict counts, latency, DB time, queue depth.
- Alerts: sync fail rate > 2% over 5m; worker lag > 60s; HTTP 5xx > 1% over 5m.
- Sink: pick Papertrail or DataDog; standardize log format and dashboards early.

## Definition of Done Hooks
- New/changed sync endpoints must include performance test updates.
- PRs that alter queries/indexes should note expected perf impact and rerun k6 smoke.
- Observability fields must be emitted for every sync request.
