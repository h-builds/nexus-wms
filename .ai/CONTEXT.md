# NexusWMS AI Context

## System Identity

NexusWMS is an agent-ready warehouse orchestration platform built as a monorepo.

It is designed to model warehouse execution, operational monitoring, and future tactical simulation.

The system currently includes:

- a Laravel 13 backend (`apps/api`)
- a Vue-based operational dashboard (`apps/vapor-monitor`)
- a Vue-based orchestration shell (`apps/orchestrator-twin`)
- a placeholder for a future field mobile app (`apps/field-agent-mobile`)

This project is not a generic CRUD app.
It is a domain-structured logistics system with future AI governance requirements.

---

## Current Project Stage

**Current stage: Phase 4.5 / Cross Alerts & UX Integration (VALIDATED AND COMPLETE)**

### Phase 0 / Foundation Core (VALIDATED AND COMPLETE)

- architectural foundation completed
- monorepo structure completed
- Laravel runtime working
- Vapor Monitor shell working
- Orchestrator Twin shell working
- initial documentation completed
- Product domain backend (API, entities, migration) completed
- Locations domain backend (API, entities, migration, block/unblock endpoint, RBAC enforcement) completed
- Inventory domain backend (API, StockItem entity with invariants, optimistic locking, internal mutation service) completed
- Movements domain backend (API, entities, mutation orchestration, Outbox validation, idempotency early-return) completed
- Incidents domain backend (API, entities, lifecycle management, metadata update endpoint, Outbox validation, idempotency early-return) completed
- Event outbox tracking (EventOutbox abstraction, sync dispatcher) completed
- Audit domain foundation (AuditLog entity, persistence, synchronous trace integration) completed
- API Alignment (global exception formatting, pagination standards, idempotency infrastructure on all POST commands) completed
- Phase 0 real-scenario validation (49 tests, 218 assertions, 8 validation areas) completed

Implemented documentation:

- `docs/ARCHITECTURE.md`
- `docs/DOMAIN_MODEL.md`
- `docs/DATA_DICTIONARY.md`
- `docs/API_SPEC.md`
- `docs/EVENT_CATALOG.md`
- `docs/SECURITY_MODEL.md`

### Phase 1 / Field-Agent Mobile Core MVP (VALIDATED AND COMPLETE)

Phase 1 completed with successful validation of all MVP flows.
Offline-first behavior implemented with IndexedDB draft persistence and honest UI state representation.

Current implementation focus:

- `apps/field-agent-mobile` (scaffolded and active)
- operational execution UX (completed for basic flows)
- offline-first local persistence foundation (completed via IndexedDB)
- sync queue foundation (completed)
- API consumption of existing inventory, incidents, locations, products, and movements endpoints (completed)

Not implemented yet (deferred beyond Phase 1):

- automatic sync replay
- background retry engine
- conflict resolution
- fully authenticated offline execution
- async event dispatch workers
- realtime websocket transport
- AI runtime logic
- digital twin engine

### Phase 2 / Operational Visibility (VALIDATED AND COMPLETE)

Phase 2 completed with successful integration of real-time monitoring via Vapor-Monitor.
WebSocket connectivity via Laravel Reverb provides zero-polling event streams for operations context.

Current implementation focus:

- `apps/vapor-monitor` (scaffolded and active)
- realtime dashboard foundation (completed)
- Laravel Reverb integration (completed)
- event-driven monitoring UI (completed)
- KPI visibility for operational decision-making (completed)
- structural occupancy visibility by zone (completed)

Not implemented yet (deferred beyond Phase 2):

- automatic sync replay for offline actions
- background retry engine for sync resolution
- multi-agent orchestration
- digital twin engine

### Phase 3 / Orchestrator Twin Lite (VALIDATED AND COMPLETE)

Phase 3 implements a read-only tactical interpretation layer over the existing warehouse data, running as a standalone spatial interface (`orchestrator-twin`). It has been successfully completed and verified.

Current implementation:

- `apps/orchestrator-twin/src/domains/` — pure domain layer (layout, occupancy, incidents, heatmap, simulation, recommendations)
- `apps/orchestrator-twin/src/components/layout/` — spatial rendering components with 2.5D depth mapping (WarehouseGrid, ZoneView, RackView, BinCell)
- layout domain consumes GET /api/locations and builds Warehouse → Zone → Aisle → Rack → Bin hierarchy
- occupancy domain consumes GET /api/inventory and derives per-location and per-zone density
- incidents domain consumes GET /api/incidents and maps active incidents to locations and zones
- recommendations domain provides deterministic rule-based engine outputting explicit, actionable operations, with priority sorting.
- 2.5D rendering maps density and incidents directly to visual volume, stacking layers, and structural anomalies via CSS transforms.

Completed sub-phases:

- Phase 3.0 — Domain Foundation
- Phase 3.1 — Spatial Rendering
- Phase 3.2 — Operational Layers
- Phase 3.3 — Intelligence Layer (Simulation & Rule Engine)
- Phase 3.4 — UX Hardening (Priority, Targets, Controls)
- Phase 3.5 — Spatial Depth (2.5D Height Mapping & Volume Perception)

Not implemented yet (deferred beyond Phase 3):

- real-time 3D physics rendering (WebGL/Three.js)
- autonomous multi-agent operational dispatch
- AI-generative layout modification

### Phase 4.1 / Integration Backbone Definition (VALIDATED AND COMPLETE)

Phase 4 defines the end-to-end event-driven architecture that connects all independent surfaces (API, Field-Agent, Vapor Monitor, Orchestrator Twin, AI Agents).
This sub-phase successfully established architectural alignment and documentation, preparing the system for safe implementation.

Current implementation focus (Documentation):

- `docs/ARCHITECTURE.md` (Integration backbone and Correlation model defined)
- `docs/FLOWS/integration-flow.md` (Cross-surface data lifecycle mapped)
- `docs/EVENT_CATALOG.md` (Consumer clarity established)
- `.ai/AGENTS.md` (Decision trace requirements set)

### Phase 4.2 / Event Pipeline Implementation (VALIDATED AND COMPLETE)

Phase 4.2 shifts focus from structural definitions to backend execution behavior, enforcing the Transactional Outbox pattern across all domain operations and integrating it safely with a deterministic event pipeline. Phase 4.2 was successfully completed and validated.

Implemented features (Phase 4.2.1, 4.2.2, 4.2.3):
- strict Transactional Outbox encapsulation across all domain boundaries
- 100% removal of Outbox bypasses (Product/Location domains)
- DB::afterCommit deterministic internal dispatching
- Outbox Integrity test suite (rollback & nested tx safety)
- At-Most-Once delivery model with strict `dispatched_at` timestamps
- Idempotent Dispatcher with concurrency lock protection

Not implemented yet (deferred beyond Phase 4.2.2):

- actual coding of backend asynchronous workers / queues
- actual coding of AI agent logic

### Phase 4.3 / Cross-Surface Consumption (ACTIVE)

Phase 4.3 connects the backend event pipeline into all real-time visual and functional surfaces (Vapor Monitor, Orchestrator Twin) via websocket bindings.

Implemented features:
- Unified event ingestion store logic (`useEventIngestionStore`) built across Vapor Monitor and Orchestrator Twin (Phase 4.3.1).
- Decoupled Reverb ingestion layer cleanly separated from domain UI reactivity constraints.
- Visual diagnostic debugger deployed on both boundaries.
- Deterministic Event Interpretation layer (`EventInterpreter` + `useEventStateStore`) translating stream facts into pure mathematical warehouse states (Phase 4.3.2).
- Strict Surface Binding eliminating component-level state mutation, tying UI reactivity directly to `InterpretedState` (Phase 4.3.3).
- Event contract alignment repair ensuring `inventory.stock.relocated` and `inventory.stock.adjusted` payloads match `EVENT_CATALOG.md` canonical definitions across backend emitters and frontend interpreters (Phase 4.3.5A).
- Drift-detection guard on `inventory.stock.adjusted` preventing silent application of stale absolute quantity adjustments under out-of-order delivery, with `DriftedEvent` diagnostic capture and `driftedEvents[]` tracking on `InterpretedState` (Phase 4.3.5B). Validated: out-of-order protection, in-order correctness, duplicate safety, cross-surface equivalence, deterministic behavior.
- Burst stability validation confirming the EventInterpreter + InterpretedState pipeline remains stable under a 50-event high-frequency burst mixing `inventory.stock.adjusted`, `inventory.stock.relocated`, and `movement.created` (Phase 4.3.5D). Validated: no state corruption, unconditional duplicate rejection, deterministic drift detection under interleaved bursts, byte-identical cross-surface convergence, baseline-independent determinism.

Not implemented yet:
- Autonomous Multi-Agent action hooks (deferred to later phases).

### Phase 4.4 / Decision Intelligence Layer (ACTIVE)

Phase 4.4 defines the formal decision intelligence model — how agents record, explain, and link their advisory outputs to the system's event-driven backbone.

Completed features:
- Decision Trace Model Definition (Phase 4.4.1): canonical `DecisionTrace` entity defined in `docs/DOMAIN_MODEL.md`, Intelligence domain established as owner, Decision Trace Layer architecture defined in `docs/ARCHITECTURE.md`, agent output contract formalized as DecisionTrace in `.ai/AGENTS.md`, domain events vs decision traces boundary clarified in `docs/EVENT_CATALOG.md`.
- DecisionTrace Persistence Layer (Phase 4.4.2): database migration, domain entity with invariant enforcement, Eloquent repository with append-only persistence, read-only API endpoints (`GET /api/intelligence/traces`, `GET /api/intelligence/traces/{id}`), `IntelligenceServiceProvider` registration.
- Advisory Emission Layer (Phase 4.4.3): `DecisionAgent` contract interface, `CanonicalEvent` value object, `InventoryAnomalyAgent` (deterministic rule: stock adjustment ≥30% triggers anomaly detection), `AgentExecutor` orchestrator with duplicate prevention via `causationId+agentId` guard, wired as synchronous listener on `BroadcastableOutboxEvent` without modifying the event pipeline. Validated: 8 tests, 25 assertions.
- Decision Trace UI Binding (Phase 4.4.4): created `useDecisionTraceStore` Pinia stores across `vapor-monitor` and `orchestrator-twin`, decoupling agent data from event streams by exclusively querying `GET /api/intelligence/decision-traces`. Added projection-only rendering panels (`DecisionTraceFeed`, `DecisionTracePanel`) that display detection, suggestion, and severity without re-evaluating core business rules.
- Decision Trace Human Interaction (Phase 4.4.5): added strict `PATCH` backend endpoints (`acknowledge`, `act-upon`, `dismiss`) to `DecisionTraceController`. Updated `useDecisionTraceStore` in both surfaces to include mutation flows. Attached reactive structural UI controls into `DecisionTraceFeed` and `DecisionTracePanel` preventing invalid transitions and decoupling agent state operations from the core event stream. Supported strict attribution checks over Action executions.
- Intelligence Layer Hardening (Phase 4.4.8): implemented strict failure isolation (`AgentExecutor` and `IntelligenceServiceProvider` structured to safely swallow and log exceptions), logical duplicate protection (using `updateOrCreate` by `causationId` and `agentId`), and rigorous payload validation. Restored random UUID generation and real agent `createdAt` time values, while guaranteeing stable entity evolution.
- Decision Trace Query Layer (Phase 4.4.6): enabled stateless filtering and ordering by integrating status, severity, agentDomain, and sort parameters into the `EloquentDecisionTraceRepository` and mapping them through the UI via dropdown controls in both `vapor-monitor` and `orchestrator-twin`. Implemented with strict adherence to stateless principles, computing no logic in the UI.
- Decision Trace Metrics / Dashboard Layer (Phase 4.4.7): added a lightweight, strictly stateless metrics layer for operators via a minimal read-only `GET /api/intelligence/decision-traces/metrics` endpoint. Introduced `getMetrics` repository aggregate method, expanded `useDecisionTraceStore` to independently fetch metrics natively alongside updates, and implemented reactive summary panels (Advisory, Acted Upon, Dismissed, Critical) in both `vapor-monitor` and `orchestrator-twin` without computing metrics logic in the UI.

### Phase 4.5 / Cross Alerts & UX Integration (VALIDATED AND COMPLETE)

Phase 4.5 shifts focus to the Product Layer, integrating the Intelligence Layer into a cohesive user experience across Vapor Monitor and Orchestrator Twin.

Completed features:
- Unified alert signals establishing a consistent visual language for advisories and anomalies.
- New trace highlighting tracking recently appeared traces securely via `recentlySeenIds` / `justAppearedIds` without backend persistence.
- Critical severity emphasis enforcing strong contrast and visual cues for critical problems.
- Cross-surface interaction feedback ensuring immediate local reflection of state changes (acknowledgments, dismissals) via CSS transitions.
- Empty / Calm state handling avoiding completely empty dashboards and displaying positive confirmation of active monitoring.

Not implemented yet (deferred beyond Phase 4):

- Additional agents (incident analysis, movement intelligence)
- Autonomous multi-agent action hooks

## Primary Mission for AI Assistants

AI assistants working in this repository must help implement the operational core of NexusWMS without breaking domain boundaries.

Primary implementation priority:

1. backend domain core
2. API contracts
3. shared types and schemas
4. monitoring UI integration
5. event consistency
6. governance artifacts

AI must optimize for correctness, traceability, and modularity — not speed alone.

---

## MVP Scope

The MVP is focused on the operational warehouse core.

In scope:

- product lookup
- inventory lookup
- location lookup
- incident registration
- movement registration
- movement traceability
- auditability foundation
- event emission foundation

Out of scope for current MVP:

- procurement workflows
- supplier evaluation
- forecasting
- passkeys
- semantic search implementation
- AI decision automation
- digital twin simulation engine
- robot orchestration
- advanced replenishment

AI must not expand scope unless explicitly requested.

---

## Sources of Truth

When reasoning about the system, use this priority order:

### 1. Domain truth

`docs/DOMAIN_MODEL.md`

Use this file to understand:

- domain boundaries
- entity roles
- ownership of data
- business rules
- state transitions

### 2. Architectural truth

`docs/ARCHITECTURE.md`

Use this file to understand:

- monorepo structure
- runtime surfaces
- module boundaries
- frontend/backend separation
- future communication model

### 3. Vocabulary truth

`docs/DATA_DICTIONARY.md`

Use this file to resolve:

- naming
- canonical terms
- allowed inventory/movement/incident values

### 4. API truth

`docs/API_SPEC.md`

Use this file to understand:

- endpoint surface
- command vs query semantics
- request/response shapes

### 5. Event truth

`docs/EVENT_CATALOG.md`

Use this file to understand:

- domain events
- event naming
- event payload semantics
- future consumers

If there is conflict:

- domain model overrides API shortcuts
- vocabulary overrides ad hoc naming
- architecture overrides convenience hacks

---

## Domain Ownership Rules

### Product

Owns product identity and master data.

### Inventory

Owns stock truth:

- quantityOnHand
- quantityAvailable
- quantityBlocked
- lot/serial context
- stock status

### Movements

Owns stock transitions.

Inventory must change through movements, not hidden direct mutation.

### Incidents

Owns anomaly registration and lifecycle.

Incidents do not directly mutate stock unless a corrective movement or adjustment is executed.

### Locations

Owns physical warehouse structure.

### Identity

Owns actor identity and attribution.

### Audit

Owns traceability of meaningful operational changes.

### Intelligence

Owns decision trace lifecycle and agent output accountability.
Decision traces are advisory records — they do not mutate domain state or emit domain events.

AI must preserve these boundaries.

---

## Current Engineering Direction

### Backend

The backend is a modular monolith in Laravel.

Each module is expected to evolve with layered separation:

- Application
- Domain
- Infrastructure

Business rules must not live inside controllers.

### Frontend

Frontend apps are organized by domain.
AI should preserve domain-oriented structure instead of creating generic utility sprawl.

### Events

Events are facts that already happened.
They are immutable and future-facing.
They support audit, observability, AI monitoring, and digital twin evolution.

---

## AI Implementation Bias

When making implementation choices, prefer:

- explicit contracts
- small composable services
- typed payloads
- auditable commands
- event-friendly design
- additive evolution

Avoid:

- hidden coupling
- controller-heavy logic
- duplicated types
- broad “helper” abstractions without ownership
- premature microservices
- speculative AI features outside MVP

---

## Operational Constraints for AI

AI must assume:

- mobile offline execution foundation is implemented (IndexedDB sync queue), but true sync conflict resolution is deferred
- realtime WebSocket transport is implemented via Laravel Reverb (vapor-monitor consumes events from the `warehouse.monitoring` channel)
- semantic search is not implemented yet
- event bus is partially implemented (Transactional Outbox dispatches synchronously; async workers are deferred)

Do not pretend features already exist.

Design for them, but do not claim they are implemented unless they actually are.

---

## Expected Near-Term Build Order

Near-term implementation order should be:

1. products (completed)
2. locations (completed — including block/unblock endpoint)
3. inventory (completed — fully integrated with Movements for mutation)
4. incidents (completed — including metadata update endpoint)
5. movements (completed — including idempotency early-return)
6. audit log foundation (completed)
7. event emission foundation (completed — Outbox dispatcher in place, pending async workers)
8. Phase 0 real-scenario validation (completed — 49 tests, 218 assertions)
9. Phase 1 field-agent-mobile core MVP (completed offline foundation & UI flows)
10. Phase 2 vapor-monitor operational visibility (completed realtime foundation & KPI dashboard)
11. Phase 3 orchestrator-twin-lite integration (completed — 2.5D spatial depth, simulation, rule-engine)
12. Phase 4.1 integration backbone definition (completed — architectural alignment)
13. Phase 4.2 event pipeline implementation (completed — outbox integrity and broadcast safety)
14. Phase 4.3 cross-surface consumption (completed — deterministic event interpretation and drift-detection)
15. Phase 4.4 decision intelligence layer (active — model definition, persistence, and advisory emission completed)
16. AI governance extensions

---

## Context Update Rule

This file must be updated when one of the following changes:

- a new domain is added
- MVP scope changes
- ownership rules change
- implementation stage changes
- a previously planned feature becomes real

### Phase 3 / Orchestrator Twin Lite (VALIDATED AND COMPLETE)

Focus:

- spatial interpretation of warehouse data
- transformation of events into visual insights
- early-stage decision support

This phase does NOT implement a full digital twin.
It establishes the foundation for:

- simulation
- orchestration
- AI-assisted decision systems

The system remains:

- read-heavy
- suggestion-only
- non-executing (no autonomous actions)

Key principle:

The Twin does not mutate the system.
It interprets it.
