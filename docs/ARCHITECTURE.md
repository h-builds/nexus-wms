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
