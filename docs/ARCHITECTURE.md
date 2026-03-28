# Architecture Overview

## System Style

NexusWMS uses a modular monolith backend with domain-separated frontend apps inside a pnpm monorepo.

## Core Domains

- Inventory
- Incidents
- Movements
- Locations
- Identity
- Audit

## Frontend Surfaces

- Field-Agent Mobile: operational capture
- Vapor-Monitor: operational control center
- Orchestrator Twin: tactical simulation and recommendations

## Integration Pattern

- REST for CRUD and command execution
- WebSockets for live updates
- Domain events for internal orchestration

## Non-Functional Priorities

1. Traceability
2. Offline resilience
3. Accessibility
4. Auditability
5. AI governance readiness
