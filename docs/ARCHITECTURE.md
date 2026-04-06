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
- real-time inventory visibility via Laravel Reverb
- incident visibility
- warehouse KPI surface (Total Inventory, Open Incidents, Differences)
- zone occupancy visibility (Relative Location Fill Rate)
- control center UI

Status: **Active** (Phase 2 completed)

Style:
- Vue 3.6
- Vite
- Pinia stores with Laravel Echo
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

Status: **Active** (Phase 1 completed)
- IndexedDB-backed offline persistence
- sync queue foundation implemented

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

### Current (Phase 2 Completed)
- REST API acts as the primary command interface and initialization query layer
- WebSockets provide live state propagation via Laravel Reverb
- Frontend apps (Vapor-Monitor) strictly separate domain contexts on incoming events
- Mobile apps (Field-Agent) utilize IndexedDB for offline persistence and sync queues

### Realtime Operational Data Flow Loop
The `vapor-monitor` realtime architecture is intentionally unidirectional and strictly decoupled from the core warehouse execution path:

1. **Mutation**: A REST command mutates system state (e.g., `POST /api/movements`).
2. **Outbox**: The domain service persists the change and writes an event to the `Outbox` table atomically.
3. **Dispatch**: The backend `OutboxDispatcher` reads the event and broadcasts a `BroadcastableOutboxEvent` to the `warehouse.monitoring` channel.
4. **Transport**: Laravel Reverb pushes the WebSocket payload to subscribed clients.
5. **Consumption**: Vue/Pinia (via Laravel Echo) intercepts the event, filtering payloads into strict domain subsets (e.g. `inventory.*` strictly controls numerical KPIs, `movement.*` controls visual feeds), applying deterministic math without re-polling the database.

If the WebSocket server dies, operations continue seamlessly. The dashboard gracefully stops updating until reconnection but never breaks core warehouse execution.

---

## Phase 4 — Integration Backbone

Phase 4 defines the end-to-end integration backbone, transforming the system from partially connected modules into a fully event-driven distributed architecture.

### End-to-End Event Flow

The flow of information across the system follows a unidirectional, strictly decoupled path:

1. **Field-Agent (Mobile/UI)**: Initiates an action (e.g., reports an incident) against the REST API.
2. **API (Modular Monolith)**: Validates the request, executes domain logic, mutates the database, and atomically persists an event to the Outbox.
3. **Event Bus (Abstraction)**: A logical decoupling layer that transports events from the domains to multiple consumers. The current MVP implementation uses a WebSocket channel (e.g., `warehouse.monitoring` via Laravel Reverb), while the architecture remains transport-agnostic and may evolve to message brokers or distributed event systems.
4. **Vapor Monitor**: Consumes the broadcast events to update real-time operational KPIs, dashboards, and zone occupancy without polling.
5. **Orchestrator Twin**: Consumes the broadcast events to update its 2.5D spatial representation (mapping incidents, blockages, and density anomalies) and rule-based recommendation engine.

### Role of Each Module

- **Field-Agent Mobile**: Action initiator. Focuses on operational execution and offline-resilience.
- **Backend API**: System of truth. Ensures invariants are maintained and atomically generates immutable domain facts (events).
- **Transport Layer / Event Bus**: The architectural role that decouples mutation from observation. It is an independent layer responsible for transporting and distributing events to multiple consumers without interfering with domain logic. Dispatcher + Reverb is simply its current implementation.
- **Vapor Monitor**: Real-time observer. Focuses on KPI visibility and immediate operational awareness.
- **Orchestrator Twin**: Spatial and tactical interpreter. Converts data and events into visual insights, simulation inputs, and decision support.

### Event Lifecycle

1. **Created**: A domain service instantiates an event summarizing state changes.
2. **Persisted**: The event is atomically saved to the Outbox table in the same database transaction as the primary entity mutation.
3. **Dispatched**: A background dispatcher safely extracts the event from the outbox table.
4. **Broadcast**: The payload is pushed over appropriate transport channels (e.g., WebSocket, message queues).
5. **Consumed**: Independent frontends (Vapor Monitor, Orchestrator Twin) and processes (AI agents) intercept, filter, and apply the event.

### Domain Events vs Derived Signals

The integration backbone distinguishes between two types of messaging paradigms:

- **Domain Events**: Immutable facts representing what *already happened* (e.g., `inventory.stock.adjusted`, `incident.reported`). They are universally authoritative and generated strictly by backend domains.
- **Derived Signals (Future)**: Suggestions, alerts, or tactical recommendations generated by AI agents or the Orchestrator Twin (e.g., a "shortage risk" signal). They are advisory and must go through validation before triggering actual domain state mutations.

## Correlation Model

Traceability across distributed components, asynchronous events, and agent decisions is managed via explicit correlation attributes.

- **`correlationId`**: Groups all related events back to a single originating request. For example, a single incident report will assign the same `correlationId` to the `incident.reported` event, the subsequent `movement.created` event, and the `inventory.stock.adjusted` event.
- **`causationId`**: Identifies the specific event that directly triggered the current event. If a backend process reacts to `movement.created` to execute a task, the resulting event must use the original movement's `eventId` as its `causationId`.

**Propagation Rules:**
- **Across Domains (Incidents & Movements)**: When an incident triggers an inventory movement, they share the `correlationId`, but the movement's `causationId` is the `eventId` of the incident.
- **Across Orchestrator / Agents**: When agents or the Orchestrator generate a decision or recommendation based on analyzed events, they must explicitly record the original `eventId` as the `causationId` of their "decision trace" to maintain end-to-end accountability.

---

## Decision Trace Layer

The Decision Trace Layer captures structured advisory outputs from agents and rule engines. It is not part of the event pipeline — it is a parallel accountability layer that records what agents detected, why, and what they recommended.

### Architecture Position

Decision traces sit outside the domain event flow:

```text
Domain Event (immutable fact)
    ↓
Agent / Rule Engine (consumes event)
    ↓
Decision Trace (advisory record, not an event)
    ↓
Intelligence Query Service (read-only access for UI/audit)
```

Decision traces are **never** written to the event outbox. They are persisted in a dedicated intelligence store and are queryable but do not participate in the event lifecycle.

### Correlation Linkage

Each DecisionTrace explicitly links to the event ecosystem via:

- **`causationId`**: The specific `eventId` that directly triggered the agent's analysis.
- **`correlationId`**: Inherited from the originating event chain, enabling end-to-end trace reconstruction.
- **`triggerEventIds`**: The full set of events the agent considered during analysis (may include events across a time window).

This means a decision trace can be traced backward to:
1. The exact event that triggered it
2. The full correlation chain (original user action → domain events → decision trace)
3. All contextual events the agent analyzed

### Lifecycle

1. **Created**: An agent or rule engine produces a decision trace with status `advisory`.
2. **Persisted**: The trace is stored in the Intelligence domain's persistence layer (NOT the event outbox).
3. **Surfaced**: UI surfaces (Vapor Monitor, Orchestrator Twin) query and display traces.
4. **Resolved**: A human actor acknowledges, acts upon, or dismisses the trace.

### Governance Constraints

- Decision traces must NOT be confused with domain events or commands.
- Decision traces must NOT trigger automatic domain state mutations in MVP.
- Decision traces must NOT be emitted through the event outbox or broadcast channels.
- Decision traces must be explainable: every trace includes `detection`, `reasoning`, and `suggestion` as explicit fields.
- Decision traces must be auditable: `status` transitions require actor attribution.

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
- monorepo structure
- Laravel 13 modular monolith (Product, Inventory, Locations, Movements, Incidents, Events, Audit)
- Full REST API with pagination, idempotency, and global error handling
- Transactional Outbox for atomic event emission
- Vapor Monitor operational dashboard with realtime WebSocket transport (Laravel Reverb)
- Field-Agent Mobile MVP with offline-first persistence (IndexedDB)
- Orchestrator Twin shell
- shared package structure
- AI governance folder scaffold
- Phase 0, 1, and 2 validated and complete

### Pending
- automatic sync replay and conflict resolution
- digital twin engine
- AI governance rules grounded in real domain flows
- multi-agent orchestration
