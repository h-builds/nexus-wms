# NexusWMS Active Plan

## Plan ID

2026-03-foundation-core

---

## Objective

Build the foundational, production-grade core of NexusWMS with:

- clean domain boundaries
- event-driven architecture
- auditability by default
- AI governance integrated from day one

This phase prioritizes correctness, traceability, and structure over feature breadth.

---

## Current Phase

FOUNDATION CORE

This phase defines the minimum system that can:

- track inventory correctly
- handle incidents safely
- emit reliable events
- support future AI agents without risk

---

## Scope (In)

### Domains

- Product
- Inventory
- Locations
- Incidents
- Movements (basic)
- Events
- Audit

---

### Core Capabilities

#### 1. Inventory Integrity

- stock tracking per location
- available vs blocked quantities
- safe adjustments
- no negative stock

---

#### 2. Incident Management

- incident creation
- classification (AI-assisted)
- inventory impact
- resolution flow
- audit trail

---

#### 3. Event System

- event emission after domain actions
- immutable event structure
- correlation and causation tracking

---

#### 4. API Layer

- clean REST endpoints
- validated inputs
- consistent response shape

---

#### 5. Auditability

- actor tracking
- timestamped changes
- no silent mutations

---

#### 6. AI Governance (Critical)

- prompt injection protection
- strict input sanitization
- AI output validation
- rule-based override of AI suggestions

---

## Scope (Out)

Not included in this phase:

- advanced picking logic
- replenishment automation
- route optimization
- multi-warehouse orchestration
- billing or financial modules
- external integrations
- real-time streaming dashboards

---

## Deliverables

### Documentation

- [x] DOMAIN_MODEL.md
- [x] API_SPEC.md
- [x] EVENT_CATALOG.md
- [x] SECURITY_MODEL.md
- [x] docs/FLOWS/incident-flow.md

---

### AI Governance

- [x] .ai/CONTEXT.md
- [x] .ai/RULES.md
- [x] .ai/DATA_GUARDRAILS.md
- [x] .ai/EVALS.md
- [x] .ai/PROMPT_LIBRARY.md
- [x] .ai/AGENTS.md

---

### Developer Governance

- [x] .ai/PR_TEMPLATE.md
- [x] .ai/COMMIT_GUIDE.md
- [x] .ai/REVIEW_CHECKLIST.md
- [x] .github/pull_request_template.md

---

### Pending (Next Actions)

- [x] implement Product domain
- [x] implement Locations domain
- [x] implement Inventory domain
- [x] implement Movements domain
- [ ] implement Incident flow (end-to-end)
- [x] implement Event emission layer (Transactional Outbox)
- [ ] implement Audit logging
- [ ] align backend with API_SPEC
- [ ] validate flows against real scenarios

---

## Implementation Order

Strict order:

1. Product
2. Locations
3. Inventory
4. Incidents
5. Events
6. Audit
7. API alignment
8. Validation scenarios

---

## Architectural Principles

- domain-first design
- thin controllers
- explicit data flow
- immutable events
- audit-first thinking
- no hidden side effects

---

## AI Principles

- AI is advisory, never authoritative
- all AI output must be validated
- no AI-generated logic is trusted blindly
- untrusted input is always sanitized
- system rules override AI decisions

---

## Risks

### 1. Overengineering too early

Mitigation:

- focus only on defined scope

---

### 2. AI misuse

Mitigation:

- strict RULES.md enforcement
- DATA_GUARDRAILS.md validation

---

### 3. Domain leakage

Mitigation:

- REVIEW_CHECKLIST.md enforcement

---

### 4. Event inconsistency

Mitigation:

- EVENT_CATALOG.md as source of truth

---

## Success Criteria

This phase is complete when:

- inventory is always consistent
- incidents are fully traceable
- events are emitted correctly
- audit trail is complete
- API matches documentation
- AI cannot corrupt system state

---

## Exit Conditions

Before moving to next phase:

- all core domains implemented
- no critical validation gaps
- no silent mutations
- all flows tested manually
- documentation and implementation aligned
- deferred phase-2 flows explicitly promoted to active work

---

## Next Phase (Preview)

OPERATIONAL EXPANSION

Will include:

- picking flows
- replenishment logic
- warehouse optimization
- agent-based automation (Vapor Monitor, Orchestrator Twin)

### Deferred Flows

The following flow documents are intentionally deferred until Inventory core is implemented and validated:

- docs/FLOWS/picking-flow.md
- docs/FLOWS/replenishment-flow.md

Condition to start them:

- Inventory domain implemented
- stock consistency rules validated
- movement registration working
- event emission foundation in place
- inbound flow implemented or validated against real behavior

---

## Notes

This plan defines the system's backbone.

Do not expand scope without updating this document.

Every PR must align with this plan.
