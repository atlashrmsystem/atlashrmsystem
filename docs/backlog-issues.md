# Backlog Issue Drafts (copy/paste into tracker)

## 1) Response Envelope Middleware
- **Summary:** Add a global JSON response envelope (`success`, `data`, `message`, `errors`) and apply to inventory endpoints.
- **Scope:** `backend/app/Providers`, middleware registration, `ProductController`, `StockController`, `StockTransferController`, related transformers.
- **Acceptance Criteria:**
  - All API responses (non-error) return the standard envelope.
  - Inventory routes return enveloped data; validation errors still use Laravel default but wrapped if possible.
  - Feature tests cover a success and validation error path.

## 2) Inventory Permission Enforcement
- **Summary:** Gate inventory endpoints with permissions per permission-matrix; seed roles/permissions.
- **Scope:** `routes/api.php`, policies (if needed), new seeder.
- **Acceptance Criteria:**
  - Permissions applied to products/stocks/transfers endpoints.
  - Seeder creates permissions (`view products`, `manage products`, `view inventory`, `adjust inventory`, `manage transfers`) and assigns to roles.
  - Feature test: unauthorized returns 403; authorized succeeds.

## 3) Sync Contract Completion
- **Summary:** Finalize `/api/sync/pull|push` to match OpenAPI: paging, delete markers, conflict codes, stable envelopes.
- **Scope:** `SyncController`, `sync_transactions` model/migration adjustments, OpenAPI docs in `docs/openapi.yaml`.
- **Acceptance Criteria:**
  - Pull supports cursor/paging and `deleted` markers.
  - Push returns machine-readable error codes; conflict codes mapped.
  - Contract tests added for pull/push happy path + conflict + replay.

## 4) Sync Load Test & Budgets
- **Summary:** Codify performance budgets and add k6/JMeter script for sync.
- **Scope:** `docs/performance-budgets.md` (or README section), `scripts/k6-sync.js`.
- **Acceptance Criteria:**
  - Budgets documented (p95 targets, payload caps).
  - k6 script runnable via `npm run k6:sync` hitting staging base URL.
  - CI (or manual) run instructions produce summary metrics.

## 5) Observability/Logging
- **Summary:** Structured JSON logging with correlation IDs; sync metrics exported to chosen sink.
- **Scope:** Logging config, middleware for correlation ID, metrics exporter setup.
- **Acceptance Criteria:**
  - Logs include `correlation_id`, `client_id`, `client_txn_id`, `scope.location_id`.
  - Metrics emit sync success/fail, conflicts, latency, queue depth.
  - Alert rules drafted (fail rate >2%/5m, worker lag >60s, 5xx >1%/5m).

## 6) Product Store & Service (Web)
- **Summary:** Introduce Pinia `productStore` and Axios service; refactor `ProductCatalog.vue`.
- **Scope:** `frontend/src/stores/product.js`, `frontend/src/services/api.js`, `ProductCatalog.vue`.
- **Acceptance Criteria:**
  - Store handles list/fetch/create/update/delete with envelope-aware service.
  - UI uses store; loading/error states handled; i18n keys used for strings.
  - Unit test for store actions with mocked service.

## 7) Axios Client + Permission Guards
- **Summary:** Centralize Axios client with auth/token injectors and permission guard helpers for inventory views.
- **Scope:** `frontend/src/services/api.js`, router guard utilities.
- **Acceptance Criteria:**
  - Single Axios instance with base URL, auth header, error interceptor.
  - Inventory routes check required permissions via helper.
  - Smoke test that unauthorized inventory access redirects/blocks.

## 8) Mobile Offline Sync Redesign
- **Summary:** Move Flutter offline queue to SQLite + `/sync` endpoints with idempotency keys and backoff.
- **Scope:** New SQLite layer, sync service, migration of existing queue data.
- **Acceptance Criteria:**
  - Offline events stored in SQLite with `client_txn_id`.
  - Flush uses `/api/sync/push`; pull consumes `/api/sync/pull` with last token.
  - Unit/integration tests cover queueing, replay after network loss, conflict handling.

## 9) Windows Shared Sync SDK + POS Skeleton
- **Summary:** Create .NET 8 class library for sync + POS WPF shell consuming it.
- **Scope:** New repo/project, DTOs, HttpClient wrapper, SQLite models, POS app bootstrap.
- **Acceptance Criteria:**
  - Library builds as NuGet package; sample POS screen pulls/pushes dummy data via SDK.
  - Idempotency and retry policy implemented.
  - README documents setup and sample usage.

## 10) Finance Schema & Controls
- **Summary:** Implement CoA, journals, journal_lines, periods, audit_logs with immutability and period close.
- **Scope:** Migrations, models, services, policy enforcement, minimal APIs.
- **Acceptance Criteria:**
  - Posted journals immutable; reversals supported.
  - Period close blocks new posts; audit logs recorded for create/post/void.
  - Feature tests for posting, reversal, period close enforcement.

## 11) Gap Matrix Governance
- **Summary:** Operationalize gap matrix tracking with weekly updates.
- **Scope:** GitHub Project/Notion board, cadence doc.
- **Acceptance Criteria:**
  - Board lists gaps with P0/P1/P2 tags linked to issues.
  - Weekly update ritual documented; owner assigned.
