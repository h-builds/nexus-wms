# NexusWMS Architecture

## Overview

NexusWMS is a modular warehouse orchestration platform built as a monorepo.

The project is designed to demonstrate progression from operational execution to tactical monitoring and strategic orchestration across three product surfaces:

- Field-Agent Mobile
- Vapor Monitor
- Orchestrator Twin

The backend is implemented as a modular monolith in Laravel 13.
Frontend surfaces are implemented as separate Vue applications inside the same monorepo.

---

## Current Monorepo Structure

```text
apps/
  api/
  vapor-monitor/
  field-agent-mobile/
  orchestrator-twin/

packages/
  shared-types/
  shared-schemas/
  event-contracts/
  prompt-library/
  ui-tokens/
```

---

## Runtime Surfaces

### 1. API

Location: `apps/api`

Purpose:
- system of record
- domain logic
- inventory state
- incidents lifecycle
- movements tracking
- auditability

Style:
- Laravel 13
- modular monolith
- domain-separated modules

---

### 2. Vapor Monitor

Location: `apps/vapor-monitor`

Purpose:
- operational monitoring
- real-time inventory visibility
- incident visibility
- warehouse KPI surface
- control center UI

Style:
- Vue 3.6
- Vite
- domain-separated frontend folders

---

### 3. Orchestrator Twin

Location: `apps/orchestrator-twin`

Purpose:
- tactical simulation
- warehouse layout visualization
- congestion awareness
- future slotting and recommendation engine

Style:
- Vue 3.6
- Vite
- future Three.js integration

---

### 4. Field-Agent Mobile

Location: `apps/field-agent-mobile`

Purpose:
- field capture
- inventory lookup
- incident registration
- offline-first warehouse operations

Status:
- workspace placeholder created
- implementation pending

---

## Backend Architecture

The backend follows a modular monolith strategy.

### Core Modules
- Inventory
- Incidents
- Movements
- Locations
- Identity
- Audit

### Future Modules
- Replenishment
- Monitoring
- Orchestration

---

## Internal Layering Pattern

Each domain module follows this structure:

- **Application**: Controllers, Requests, API resources
- **Domain**: Entities, Value Objects, Domain Events, Services
- **Infrastructure**: Persistence implementations, external API clients

This keeps:
- business rules away from controllers
- persistence details away from domain logic
- future extraction possible if scaling requires it

---

## Module Dependency Rules

Inter-module dependencies must be explicit and controlled. The following directed dependency graph defines allowed runtime dependencies between backend modules:

```text
Movements  → Inventory (request StockItem creation, update quantities)
Movements  → Locations  (validate location existence and block status)
Movements  → Product    (validate product existence)
Incidents  → Movements  (create adjustment movements for stock blocking)
Incidents  → Product    (validate product existence)
Incidents  → Locations  (validate location existence)
Audit      ← ALL        (all modules write audit entries)
Identity   ← ALL        (all modules resolve actor identity)
```

**Prohibited dependencies:**

- Inventory must **NOT** depend on Incidents or Movements (it is the innermost domain)
- Locations must **NOT** depend on Inventory or Movements
- Product must **NOT** depend on any other domain module
- No circular dependencies are permitted

**Inter-module communication** within the modular monolith is via direct service injection (Laravel dependency injection). No HTTP calls between modules. No event-driven inter-module commands (events are for notification, not invocation).

---

## Caching Strategy

### Reference data (rarely changes)

The following data changes infrequently and should be cached at the application level:

| Resource | Cache TTL | Invalidation |
| :--- | :--- | :--- |
| Products | 5 minutes | On `product.created` or product update |
| Locations | 5 minutes | On `location.blocked`, `location.unblocked`, or location create |
| Warehouses | 15 minutes | On warehouse configuration change |

### Operational data (changes frequently)

| Resource | Cache | Strategy |
| :--- | :--- | :--- |
| Inventory (StockItem) | No application cache | Always read from DB (concurrency-critical) |
| Movements | No application cache | Append-only, query from DB |
| Incidents | No application cache | State changes frequently |

### HTTP cache headers

All `GET` endpoints should return:

- `Cache-Control: no-cache` for operational data (inventory, incidents, movements)
- `Cache-Control: max-age=300` for reference data (products, locations)
- `ETag` header for all single-resource endpoints (`GET /api/{resource}/{id}`)

> [!NOTE]
> StockItem data must never be cached at the application or HTTP level due to concurrency requirements. Stale stock data leads to overselling.

---

## Idempotency Store

API commands requiring the `Idempotency-Key` header (e.g., `POST /api/movements`) rely on a centralized concurrency-safe store to prevent duplicate execution.

### Storage Mechanism (MVP)

- **Technology**: Redis.
- **Data Structure**: Key-Value mapping `idempotency_key:{unique_key} -> { status, response_body, status_code }`.
- **TTL (Time to Live)**: 24 hours.

### Execution Flow

1. API Middleware attempts a Redis `SETNX` (SET if Not eXists) with the idempotency key, marking it as `in_progress`.
2. If `SETNX` fails (key exists):
   - If status is `completed`, immediately return the cached `response_body` and `status_code`.
   - If status is `in_progress`, return `409 Conflict` (concurrent request race).
3. If `SETNX` succeeds, process the request.
4. After transaction commit, update the Redis key with status `completed` and the finalized HTTP response payload.
5. If the transaction rolls back or fails with a 5xx error, delete the Redis key to allow safe retries.

This guarantees that network retries from mobile clients do not result in double-receipts or double-incidents without requiring complex locking on the primary SQL database.

---

## Frontend Architecture

Frontend applications are organized by domain instead of by generic technical type only.

Example pattern:
- `domains/inventory`
- `domains/incidents`
- `domains/monitoring`
- `domains/layout`
- `domains/simulation`

This allows:
- better ownership
- easier scaling
- clearer AI governance later
- less accidental coupling

---

## Shared Contracts

Shared packages exist to prevent duplication across apps.

### shared-types
Canonical TypeScript types shared by multiple frontends.

### shared-schemas
Validation schemas for shared payload structures.

### event-contracts
Cross-surface event definitions used to standardize operational events.

### ui-tokens
Shared visual primitives and future design consistency layer.

### prompt-library
Reserved for future AI prompt versioning and governance.

---

## Communication Model

### Current
- each app runs independently
- no live API integration yet
- no realtime events connected yet

### Planned
- REST API for commands and queries
- WebSockets for live state propagation
- event-driven updates across UI surfaces

---

## Non-Functional Priorities

- **Traceability**: Audit logs for every movement
- **Offline resilience**: Critical for field agents
- **Auditability**: Required for compliance
- **Accessibility**: High-contrast, keyboard-navigable
- **Domain boundaries**: Strict modularity
- **AI governance readiness**: Structured data for LLM agents

---

## Current Status

### Implemented
- monorepo
- Laravel app
- Vapor Monitor shell
- Orchestrator Twin shell
- shared package structure
- initial documentation structure
- AI governance folder scaffold

### Pending
- mobile app implementation
- domain entities and migrations
- API endpoints
- realtime integration
- digital twin engine
- AI governance rules grounded in real domain flows
