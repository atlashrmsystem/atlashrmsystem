# Gap Matrix and Execution Addendum (2026-03-06)

## Snapshot Gap Matrix
| Area | Current | Target | Gap | Priority |
| --- | --- | --- | --- | --- |
| POS/Windows apps | Not started | Offline-first WPF with hardware + sync | No repos, no SDK, no UI | P0 |
| Inventory data model | Products/ingredients/stocks/transfers only | Full catalog, pricing/discounts, stock ledger, stock adjustments | Missing ledger, pricing, adjustments | P0 |
| Sales | `sales_entries` daily aggregate | Orders with sale_items, payments, taxes, refunds | Missing order model + payments | P0 |
| Finance | None | CoA, journals, AR/AP, audit trail, period close | Entire module absent | P0 |
| Sync | Basic pull/push, no deletes/paging | Conflict-aware, paged, delete markers, OpenAPI | Incomplete contract and logic | P0 |
| Permissions | Inventory routes ungated | Permission matrix enforced | Middleware + seeds missing | P1 |
| Web | Router + views, Pinia unused, ad-hoc API calls | Domain stores + service layer, i18n-only strings | Refactor needed | P1 |
| Mobile offline | Queue to live endpoints | SQLite cache + `/sync` with idempotency | Client sync redesign | P1 |
| Tests/CI | HRM-only tests, no CI | Full-stack tests + audits in CI | Pipelines missing | P1 |
| Observability | Basic logging | Structured logs, metrics, alerts | Telemetry plan missing | P1 |

## Performance Budgets (initial)
- `/api/sync/push`: p95 < 2s at 100 concurrent; batch size ≤ 1 MB / 1,000 records.
- `/api/sync/pull`: p95 < 2s for 10k rows paged (≤ 1,000 rows per page); cursor token required.
- Worker queue latency: p95 < 30s from enqueue to completion.
- POS online checkout (local → API) p95 < 5s.
- Error rates: HTTP 5xx < 1% per 5-minute window.

## Observability Baseline
- Structured JSON logs (include `correlation_id`, `client_id`, `client_txn_id`, `scope.location_id`).
- Metrics: sync success/fail counts, conflict counts, latency, DB time, queue depth.
- Alerts: sync fail rate >2% over 5m; worker lag >60s; HTTP 5xx >1% over 5m.
- Sink: pick Papertrail or DataDog; standardize log format and dashboards early.

## Shared Windows Sync SDK
- Deliver as .NET 8 class library (private NuGet).
- Contents: DTOs for `/api/sync/*`, SQLite entities, idempotency store, retry/backoff policy, conflict code map, pluggable HttpClient transport.
- Consumers: POS, warehouse, coffee-bean, HRM desktop apps.

## Immediate Tickets (ready to open)
1) Backend: response envelope middleware and apply to inventory routes.  
2) Backend: add permission middleware to products/stocks/transfers; seed permissions.  
3) Backend: finalize `/sync` OpenAPI + contract tests; add paging/delete markers/conflict codes; expand `sync_transactions` metrics.  
4) Backend: publish performance budgets in repo and add k6/JMeter script for sync load test.  
5) Backend: structured logging + correlation IDs; export metrics to chosen sink.  
6) Frontend: Pinia `productStore` + API service; refactor `ProductCatalog.vue` to envelope-aware service.  
7) Frontend: central Axios client with auth/permission guards for inventory views.  
8) Mobile: move offline queue to SQLite + `/sync` with idempotency keys and backoff.  
9) Windows: create repo + shared sync SDK skeleton; wire POS shell to it.  
10) Finance: add CoA/journals/journal_lines/periods/audit_logs migrations with immutability checks.  
11) Governance: publish gap matrix board (GitHub Project/Notion) and schedule weekly update.
