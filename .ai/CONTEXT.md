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

Current stage:

- architectural foundation completed
- monorepo structure completed
- Laravel runtime working
- Vapor Monitor shell working
- Orchestrator Twin shell working
- initial documentation completed
- Product domain backend (API, entities, migration) completed
- Locations domain backend (API, entities, migration, documentation alignment) completed
- Inventory domain backend (read-only API, StockItem entity with invariants, migration with CHECK constraints, event contracts) completed

Implemented documentation:

- `docs/ARCHITECTURE.md`
- `docs/DOMAIN_MODEL.md`
- `docs/DATA_DICTIONARY.md`
- `docs/API_SPEC.md`
- `docs/EVENT_CATALOG.md`

Not fully implemented yet:

- remaining domain entities in Laravel (Incidents, Movements, etc.)
- remaining migrations (incidents, movements, audit_logs, event_outbox)
- remaining API routes/controllers/resources
- inventory mutation (comes through Movements domain)
- event dispatch wiring
- realtime events
- mobile app
- AI runtime logic
- digital twin engine

---

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

- mobile/offline logic is not implemented yet
- realtime transport is not implemented yet
- semantic search is not implemented yet
- event bus is not implemented yet
- audit persistence is not implemented yet

Do not pretend features already exist.

Design for them, but do not claim they are implemented unless they actually are.

---

## Expected Near-Term Build Order

Near-term implementation order should be:

1. products (completed)
2. locations (completed)
3. inventory (completed — read-only, event contracts prepared, awaiting Movements for mutation)
4. incidents
5. movements
6. audit log foundation
7. event emission foundation
8. vapor-monitor integration
9. AI governance extensions

---

## Context Update Rule

This file must be updated when one of the following changes:

- a new domain is added
- MVP scope changes
- ownership rules change
- implementation stage changes
- a previously planned feature becomes real
