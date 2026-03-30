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

## Phase 3 — Orchestrator Twin Lite ✅ VALIDATED AND COMPLETE

**Started:** 2026-03-30
**Completed:** 2026-03-30

Phase 3 has been fully validated through browser automation against the real UI, confirming spatial rendering, operational overlays, simulation flow, deterministic recommendations, and 2.5D visual density mapping.

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

- [x] layout rendering engine (grid-based spatial projection)
- [x] occupancy mapping from inventory API (domain layer)
- [x] incident + blocked location overlays
- [x] heatmap visualization (simple aggregation)
- [x] simulation input panel (manual scenario)
- [x] recommendation output (rule-based domain service)

### Success Criteria

- [x] user can identify high-density zones visually
- [x] user can detect problem areas without reading tables
- [x] system provides at least one actionable suggestion
- [x] visualization reflects real backend state (no mock drift)

### Exit Conditions

- [x] layout + occupancy working end-to-end
- [x] incidents mapped spatially
- [x] basic simulation working
- [x] recommendation logic visible in UI

### Phase 3.1 — Spatial Rendering ✅

- [x] grid renderer
- [x] zones visualized
- [x] no business logic in UI

### Phase 3.2 — Operational Layers ✅

- [x] occupancy
- [x] incidents
- [x] blocked

### Phase 3.3 — Intelligence Layer ✅

- [x] heatmap
- [x] simulation
- [x] recommendations

### Phase 3.4 — UX Hardening ✅

- [x] toggles (occupancy, incidents, heatmap layer visibility)
- [x] panels (SimulationPanel, RecommendationsPanel — extracted standalone components)
- [x] clarity (legend, priority badges, explicit target actions, responsive layout)

---

### Phase 3.5 — Spatial Depth (2.5D Rendering) ✅

- [x] perspective (BinCell lid and depth edges)
- [x] depth (density-scaled shadows and vertical lift)
- [x] stacking (internal segmentation for occupied bins)
- [x] height mapping (data → visual volume mapping, expanded contrast)
- [x] anomaly representation (structural skew and crack gradients for incidents)

---

## Phase 4 — Total Integration 🚧 IN PROGRESS

**Started:** 2026-03-30

### Objective

Transform NexusWMS into a fully integrated distributed system by introducing a unified event-driven backbone that connects all modules.

This phase establishes:

- end-to-end system orchestration
- cross-module communication via events
- full traceability using correlation models
- decision accountability through structured logging

---

### Phase 4.1 — Integration Backbone Definition ✅

**Type:** Architecture Alignment (NO implementation)

- [x] align architecture to a true event-driven model
- [x] define how the 3 modules connect and communicate
- [x] establish the correlation model (correlationId, causationId)
- [x] define decision logging concept (non-persistent)

**Exit Criteria:**

- architecture clearly defines event flow across all modules
- correlation model is standardized across the system
- integration flow is documented end-to-end
- system reads as a distributed event-driven architecture

---

### Phase 4.2 — Event Pipeline Implementation

**Type:** Backend Infrastructure

- [ ] implement transactional outbox pattern
- [ ] ensure consistent event broadcasting
- [ ] normalize event structure across domains

**Exit Criteria:**

- events are emitted reliably after DB commit
- no lost or duplicated events
- event format is consistent and aligned with EVENT_CATALOG

---

### Phase 4.3 — Cross-Surface Consumption

**Type:** Integration Layer

- [ ] Vapor Monitor consumes real-time events
- [ ] Orchestrator Twin consumes the same event stream
- [ ] correlation data is visible across all clients

**Exit Criteria:**

- all modules react to the same source of truth (event stream)
- event-driven UI updates are observable
- correlationId is traceable across surfaces

---

### Phase 4.4 — Decision Intelligence Layer

**Type:** Intelligence + Persistence

- [ ] Orchestrator generates actionable recommendations
- [ ] persist decision logs (decision trace)
- [ ] establish full event → decision traceability

**Exit Criteria:**

- decisions are explainable and linked to events
- decision logs can be queried and audited
- system demonstrates reasoning capability (not just visualization)

---

### Phase 4.5 — Cross Alerts & UX Integration

**Type:** Product Layer

- [ ] unified alert system across modules
- [ ] visual feedback loops between surfaces
- [ ] highlight anomalies and system reactions in real-time

**Exit Criteria:**

- alerts are triggered by real events
- users can observe system reactions across modules
- UX reflects a cohesive, intelligent system

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

Phase 2 / Operational Visibility was validated and completed on 2026-03-29.

Phase 3 / Orchestrator Twin Lite was validated and completed on 2026-03-30.

Phase 4 / Total Integration started on 2026-03-30.

Every PR must align with this plan.
