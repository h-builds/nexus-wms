# NexusWMS Flows

## Purpose

This directory contains operational flow documents that connect:

- domain behavior
- API contracts
- event emission
- audit requirements
- AI governance constraints

Flows are implemented progressively by phase.

---

## Active in Current Phase

These flows belong to the current foundation/core phase:

- `incident-flow.md`
- `inbound-flow.md`

These define the minimum operational backbone required for:

- inventory integrity
- incident traceability
- movement-based stock changes
- event-driven architecture

---

## Deferred Until Inventory Core Is Implemented

These flows are intentionally deferred to the next phase:

- `picking-flow.md`
- `replenishment-flow.md`

They must not be treated as active implementation scope until the following conditions are true:

- Inventory domain implemented
- stock consistency rules validated
- movement registration working
- event emission foundation in place
- inbound behavior validated

---

## Flow Maturity Rule

A flow document should only be promoted to active status when:

- its dependent domains are implemented or stabilized
- its rules can be grounded in real behavior
- it does not rely on speculative domain assumptions

This prevents documentation drift and fake completeness.

---

## Current Priority Order

1. incident-flow.md
2. inbound-flow.md
3. picking-flow.md
4. replenishment-flow.md

---

## Notes

Flows are part of system design, not decorative documentation.

They must remain aligned with:

- `docs/DOMAIN_MODEL.md`
- `docs/API_SPEC.md`
- `docs/EVENT_CATALOG.md`
- `docs/SECURITY_MODEL.md`
- `.ai/active/PLAN.md`
