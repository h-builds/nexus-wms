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

**Current stage: Phase 2 / Operational Visibility (IN PROGRESS)**

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

### Phase 2 / Operational Visibility (IN PROGRESS)

Current implementation focus:

- `apps/vapor-monitor`
- realtime dashboard foundation
- Laravel Reverb integration
- event-driven monitoring UI
- KPI visibility for operational decision-making
- occupancy visibility by zone

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
- realtime websocket transport
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

- mobile offline execution foundation is implemented (IndexedDB sync queue), but true sync conflict resolution is deferred to Phase 2
- realtime transport is not implemented yet
- semantic search is not implemented yet
- event bus is not implemented yet

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
10. vapor-monitor integration
11. AI governance extensions

---

## Context Update Rule

This file must be updated when one of the following changes:

- a new domain is added
- MVP scope changes
- ownership rules change
- implementation stage changes
- a previously planned feature becomes real
