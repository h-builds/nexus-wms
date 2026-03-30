# NexusWMS Active Plan

## Plan ID

2026-03-field-agent-mobile

---

## Objective

Build the foundational, production-grade core of NexusWMS with:

- clean domain boundaries
- event-driven architecture
- auditability by default
- AI governance integrated from day one

This phase prioritizes correctness, traceability, and structure over feature breadth.

---

## Phase 0 — Foundation Core ✅ COMPLETE

**Completed:** 2026-03-29

This phase defined and validated the minimum system that can:

- track inventory correctly
- handle incidents safely
- emit reliable events
- support future AI agents without risk

### Scope (In)

#### Domains

- Product
- Inventory
- Locations
- Incidents
- Movements (basic)
- Events
- Audit

#### Core Capabilities

1. **Inventory Integrity** — stock tracking per location, available vs blocked quantities, safe adjustments, no negative stock
2. **Incident Management** — incident creation, classification (AI-assisted), inventory impact, resolution flow, audit trail
3. **Event System** — event emission after domain actions, immutable event structure, correlation and causation tracking
4. **API Layer** — clean REST endpoints, validated inputs, consistent response shape
5. **Auditability** — actor tracking, timestamped changes, no silent mutations
6. **AI Governance** — prompt injection protection, strict input sanitization, AI output validation, rule-based override of AI suggestions

#### Scope (Out — Deferred to Later Phases)

- advanced picking logic
- replenishment automation
- route optimization
- multi-warehouse orchestration
- billing or financial modules
- external integrations
- real-time streaming dashboards

### Deliverables

#### Documentation

- [x] DOMAIN_MODEL.md
- [x] API_SPEC.md
- [x] EVENT_CATALOG.md
- [x] SECURITY_MODEL.md
- [x] docs/FLOWS/incident-flow.md

#### AI Governance

- [x] .ai/CONTEXT.md
- [x] .ai/RULES.md
- [x] .ai/DATA_GUARDRAILS.md
- [x] .ai/EVALS.md
- [x] .ai/PROMPT_LIBRARY.md
- [x] .ai/AGENTS.md

#### Developer Governance

- [x] .ai/PR_TEMPLATE.md
- [x] .ai/COMMIT_GUIDE.md
- [x] .ai/REVIEW_CHECKLIST.md
- [x] .github/pull_request_template.md

#### Implementation

- [x] implement Product domain
- [x] implement Locations domain
- [x] implement Inventory domain
- [x] implement Movements domain
- [x] implement Incident flow (end-to-end)
- [x] implement Event emission layer (Transactional Outbox)
- [x] implement Audit logging
- [x] align backend with API_SPEC
- [x] validate flows against real scenarios (49 tests, 218 assertions, 8 scenarios, 0 failures)

### Implementation Order (Completed)

1. Product
2. Locations
3. Inventory
4. Incidents
5. Events
6. Audit
7. API alignment
8. Validation scenarios

### Success Criteria (All Met)

- ✅ inventory is always consistent (verified: quantityOnHand = quantityAvailable + quantityBlocked, no negative stock)
- ✅ incidents are fully traceable (verified: full lifecycle audit + events, status machine enforced)
- ✅ events are emitted correctly (verified: transactional outbox for all state-changing operations)
- ✅ audit trail is complete (verified: all movements, incidents, and location changes audited)
- ✅ API matches documentation (verified: envelopes, pagination, error codes, camelCase, actor identity)
- ✅ AI cannot corrupt system state (verified: RBAC enforcement, immutable fields rejected, actor identity server-derived)

### Exit Conditions (All Met)

- ✅ all core domains implemented
- ✅ no critical validation gaps (5 gaps found and fixed during validation)
- ✅ no silent mutations
- ✅ all flows tested (49 automated tests across 8 validation scenarios)
- ✅ documentation and implementation aligned

---

## Phase 1 — Field-Agent Mobile Core MVP ✅ VALIDATED AND COMPLETE

**Started:** 2026-03-29
**Completed:** 2026-03-29

Phase 1 has been fully validated through manual end-to-end scenarios covering:

- product lookup
- stock visibility
- incident reporting
- movement execution
- offline draft persistence

All flows behave correctly within MVP scope.

### Objective

Build the first operational execution surface of NexusWMS through a mobile-first field workflow. This phase delivers a working mobile application that warehouse operators can use to perform core tasks in the field, with an offline-first architecture to handle connectivity gaps.

### Scope (In)

- incident registration UI
- product lookup UI
- location selection UI
- stock lookup UI
- simple movement registration UI
- offline-first local persistence foundation
- sync queue foundation

### Scope (Out — Deferred)

- no Vapor Monitor work yet
- no realtime dashboard
- no advanced FIFO/FEFO
- no full sync conflict resolution
- no semantic search implementation unless already backed by real API capability
- no production-grade voice pipeline unless grounded in actual backend support

### Deliverables

- [x] product lookup flow
- [x] stock lookup flow
- [x] incident registration flow
- [x] simple movement registration flow
- [x] local offline persistence foundation
- [x] sync queue foundation

### Success Criteria

- [x] operator can look up products and view stock levels on mobile
- [x] operator can register incidents from the field
- [x] operator can execute simple movements (inbound, outbound, transfer)
- [x] all operations persist locally when offline
- [-] queued operations sync when connectivity is restored (Deferred to Phase 2)
- [x] all UI flows consume the validated Phase 0 REST API

### Exit Conditions

- [x] all deliverables completed and tested
- [x] offline persistence verified with simulated connectivity loss
- [-] sync queue correctly replays queued operations (Deferred to Phase 2)
- [x] no regressions in Phase 0 backend (test suite still green)
- [x] documentation updated to reflect mobile architecture

---

## Phase 2 — Operational Visibility ✅ VALIDATED AND COMPLETE

**Started:** 2026-03-29
**Completed:** 2026-03-29

Phase 2 has been formally validated through manual end-to-end integration mapping frontend state directly against Laravel Reverb.

### Objective

Build the first realtime operational monitoring surface of NexusWMS through Vapor-Monitor.

This phase demonstrates the ability to handle event-driven warehouse visibility at scale using a decision-oriented dashboard, not a presentation-only UI.

### Scope (In)

- realtime dashboard for inbound, outbound, and incidents
- WebSocket integration via Laravel Reverb
- basic KPIs (inventory, incidents, differences)
- occupancy view by zone
- event-aligned frontend monitoring state

### Scope (Out)

- purchasing / suppliers
- forecasting / advanced analytics
- digital twin simulation
- multi-agent orchestration
- high-polish UI work

### Deliverables

- [x] realtime dashboard connected to real backend data
- [x] realtime incident updates via Reverb
- [x] realtime inventory-related updates via Reverb
- [x] KPI widgets for total inventory, open incidents, and differences
- [x] occupancy-by-zone view
- [x] domain-structured frontend monitoring state

### Success Criteria

- [x] operator/supervisor can see inbound, outbound, and incident state in one dashboard
- [x] dashboard updates without manual refresh for supported events
- [x] KPI values stay aligned with backend state
- [x] occupancy can be understood quickly by zone
- [x] no fake realtime behavior is introduced

### Exit Conditions

- [x] Vapor-Monitor provides real operational visibility
- [x] Reverb transport is working for the selected MVP events
- [x] frontend state remains aligned with event contracts
- [x] documentation updated to reflect monitoring architecture

---
## Phase 3 — Orchestrator Twin Lite (IN PROGRESS)

### Objective

Demonstrate tactical orchestration capability by transforming operational data into spatial intelligence and decision support.

### Scope (In)

- warehouse layout representation (zones, racks, bins)
- occupancy visualization (per location / zone)
- incident spatial mapping
- blocked location visibility
- simple heatmap (activity or anomalies)
- simulation input (hypothetical inbound load)
- rule-based recommendation engine (basic)

### Scope (Out)

- no real-time physics simulation
- no multi-agent orchestration
- no optimization algorithms
- no full digital twin engine

### Deliverables

- [ ] layout rendering engine (2.5D)
- [ ] occupancy mapping from inventory API
- [ ] incident + blocked location overlays
- [ ] heatmap visualization (simple aggregation)
- [ ] simulation input panel (manual scenario)
- [ ] recommendation output (rule-based)

### Success Criteria

- [ ] user can identify high-density zones visually
- [ ] user can detect problem areas without reading tables
- [ ] system provides at least one actionable suggestion
- [ ] visualization reflects real backend state (no mock drift)

### Exit Conditions

- [ ] layout + occupancy working end-to-end
- [ ] incidents mapped spatially
- [ ] basic simulation working
- [ ] recommendation logic visible in UI

### Phase 3.1 — Spatial Rendering

- [ ] grid renderer
- [ ] zones visualized
- [ ] no business logic in UI

### Phase 3.2 — Operational Layers

- [ ] occupancy
- [ ] incidents
- [ ] blocked

### Phase 3.3 — Intelligence Layer

- [ ] heatmap
- [ ] simulation
- [ ] recommendations

### Phase 3.4 — UX Hardening

- [ ] toggles
- [ ] panels
- [ ] clarity
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

Mitigation: focus only on defined scope

### 2. AI misuse

Mitigation: strict RULES.md enforcement, DATA_GUARDRAILS.md validation

### 3. Domain leakage

Mitigation: REVIEW_CHECKLIST.md enforcement

### 4. Event inconsistency

Mitigation: EVENT_CATALOG.md as source of truth

### 5. Offline data conflicts (Phase 1)

Mitigation: queue-based sync with server-side idempotency, conflict detection deferred to Phase 2

---

## Notes

This plan defines the system's backbone.

Phase 0 / Foundation Core was validated and completed on 2026-03-29.

Phase 1 / Field-Agent Mobile Core MVP was validated and completed on 2026-03-29.

Every PR must align with this plan.

