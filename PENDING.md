# Pending Files for Review

> **Total: 28 code files** — 16 PHP (Part A) + 3 Frontend (Part B) — plus 9 meta files (Part C)

---

## Part A — PHP / API Files (16 files, 6 groups)

### Group A1 — Events + Incidents + Locations (Providers + Actions)

- New: `apps/api/app/Modules/Events/Infrastructure/Providers/EventServiceProvider.php`
- Modified: `apps/api/app/Modules/Incidents/Application/Actions/UpdateIncidentStatusAction.php`
- Modified: `apps/api/app/Modules/Locations/Application/Actions/CreateLocationAction.php`

### Group A2 — Locations (Actions + DTOs)

- Modified: `apps/api/app/Modules/Locations/Application/Actions/UpdateLocationStatusAction.php`
- New: `apps/api/app/Modules/Locations/Application/DTOs/LocationCreatedEventPayload.php`
- New: `apps/api/app/Modules/Locations/Application/DTOs/LocationStatusUpdatedEventPayload.php`

### Group A3 — Movements + Product (Actions + DTOs)

- Modified: `apps/api/app/Modules/Movements/Application/Actions/RegisterMovementAction.php`
- Modified: `apps/api/app/Modules/Product/Application/Actions/CreateProductAction.php`
- New: `apps/api/app/Modules/Product/Application/DTOs/ProductCreatedEventPayload.php`

### Group A4 — Other + Tests (Misc + Tests)

- Modified: `apps/api/bootstrap/providers.php`
- New: `apps/api/tests/Feature/Events/CrossSurfaceEventContractTest.php`
- New: `apps/api/tests/Feature/Intelligence/DecisionTraceIntegrationTest.php`

### Group A5 — Tests (Tests)

- Modified: `apps/api/tests/Feature/Movements/RegisterMovementTest.php`
- Modified: `apps/api/tests/Feature/Outbox/OutboxIntegrityTest.php`
- Modified: `apps/api/tests/Feature/Validation/BlockedLocationTest.php`

### Group A6 — Tests (Tests)

- Modified: `apps/api/tests/Feature/Validation/ConcurrencyTest.php`

---

## Part B — Frontend / Vue / TypeScript Files (3 files, 1 groups)

### Group B1 — Orchestrator Twin + Vapor Monitor (Components + Tests)

- Modified: `apps/orchestrator-twin/src/domains/events/components/EventLogDebugger.vue`
- Modified: `apps/orchestrator-twin/src/domains/events/stores/useEventStateStore.ts`
- Modified: `apps/vapor-monitor/src/domains/events/components/EventLogDebugger.vue`

---

## Part C — Meta / Tooling Files (9 files)

- Modified: `.ai/AGENTS.md`
- Modified: `.ai/CONTEXT.md`
- Modified: `.ai/CURRENT_TASK.md`
- Modified: `.ai/active/PLAN.md`
- New: `.codex`
- New: `CODEREVIEWRULES.md`
- Modified: `README.md`
- Modified: `docs/API_SPEC.md`
- Modified: `docs/DATA_DICTIONARY.md`

