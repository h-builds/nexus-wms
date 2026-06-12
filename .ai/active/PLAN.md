# NexusWMS Active Plan

## Plan ID

2026-06-mission-control-solutions-engineering-demo

---

## Current Stage

**Phase 5 / Mission Control & Enterprise Solutions Engineering Demo — ACTIVE**

Phases 0 through 4.5 are considered complete and must not be rebuilt. The current goal is not to add random features. The goal is to package the existing operational core, realtime event pipeline, decision intelligence layer, and warehouse simulation surfaces into a single high-impact technical demo that proves AI Solutions Engineering capability.

NexusWMS is not positioned as a generic SaaS product, a wrapper around an AI API, or a commercial subscription app. It is positioned as a portfolio-grade, enterprise-style integration case study for warehouse operations, legacy-system adaptation, realtime monitoring, event-driven UI, explainable AI decision traces, and resilient field execution.

---

## Baseline Already Completed

The following capabilities are treated as existing foundations:

- Laravel modular monolith API with domain boundaries.
- Product, Locations, Inventory, Movements, Incidents, Events, Audit, and Intelligence foundations.
- Transactional Outbox with event broadcasting through Laravel Reverb.
- Vapor Monitor realtime operational dashboard.
- Field-Agent Mobile execution surface with IndexedDB-backed offline draft persistence and sync queue foundation.
- Orchestrator Twin Lite with 2.5D warehouse layout, occupancy, incidents, heatmap, simulation, and deterministic recommendations.
- Decision Trace persistence, query layer, metrics, UI binding, and human interaction flows.
- Cross-surface event interpretation, drift detection, burst stability, and alert feedback loops.

Previous phases remain valuable, but they are now part of the foundation, not the active plan.

---

## Strategic Objective

Build a unified demo experience that lets a recruiter, technical lead, architect, or engineering manager understand the system in under 30 seconds and then inspect its engineering depth in under 5 minutes.

The demo must prove four things:

1. **Operational execution:** warehouse workers can report incidents, inspect stock, and prepare movements from a field interface.
2. **Realtime visibility:** operational events propagate to monitoring surfaces without polling or manual refresh.
3. **Spatial intelligence:** the warehouse layout reacts to incidents, occupancy pressure, and blocked locations.
4. **Governed AI:** intelligence is explainable, auditable, suggestion-first, and never allowed to mutate domain state without an authorized command path.

---

## Product Narrative

### Positioning

NexusWMS demonstrates how modern AI Solutions Engineering works inside traditional operational environments.

The project does not say:

> “I built a SaaS app with AI features.”

It says:

> “I engineered an event-driven logistics system that connects field execution, realtime monitoring, spatial simulation, legacy data ingestion, and explainable AI decision support without breaking domain invariants.”

### Demo Promise

A visitor should be able to press one button and see the entire architecture react:

```text
Scenario Trigger
  -> Field/Legacy/Input Command
  -> Laravel Domain Logic
  -> Transactional Outbox
  -> Reverb Broadcast
  -> Vapor Monitor Telemetry
  -> Orchestrator Twin Spatial Update
  -> Decision Trace Explanation
  -> Human-Approved Mitigation
  -> Audited Operational Result
```

---

## Non-Negotiable Engineering Guardrails

### 1. No Fake Completeness

Do not claim a feature is production-ready unless it is backed by real code, real API behavior, or clearly labeled demo simulation.

Allowed:

- demo scenario seeders
- synthetic warehouse data
- controlled simulation endpoints
- read-only what-if calculations
- explicitly labeled demo mode

Forbidden:

- fake realtime
- fake AI autonomy
- fake ERP integration presented as real SAP/AS400 connectivity
- mocked state mutations that bypass the API
- frontend-only business rules that should belong to backend domains

### 2. AI Remains Suggestion-First

In this phase, AI and deterministic intelligence may detect, explain, and suggest. They must not silently mutate inventory, block locations, or execute movements.

Any state-changing mitigation must flow through an authorized command path:

```text
DecisionTrace suggestion
  -> human clicks Apply / Act upon
  -> authorized backend command
  -> domain validation
  -> audit log
  -> domain event
  -> realtime UI update
```

### 3. Inventory Integrity Is Untouchable

Inventory state must only change through explicit movements or adjustments. Incidents, agents, simulations, and UI panels must not directly mutate stock.

### 4. Simulation Must Be Isolated

Public demo traffic must not poison the shared database. Every stress scenario must be either:

- scoped to an explicit demo session, or
- written with a `simulationRunId`, or
- cleaned through a controlled purge/reset command.

### 5. One Screen, Real System

Mission Control should reduce demo friction, not replace the architecture. It should be a unified viewport over the real system behavior, not a detached mock landing page.

---

## Phase 5 Scope

### In Scope

- Add a unified `apps/mission-control` demo surface.
- Build a controlled scenario trigger system for industrial stress and incident demos.
- Add demo-session isolation and reset/purge mechanics.
- Add a legacy ERP import simulator using CSV/XML-like synthetic data.
- Add a technical narrative layer that explains architecture decisions inline.
- Improve visual density and portfolio storytelling across existing surfaces.
- Add end-to-end demo validation and browser walkthrough tests.
- Update README, CONTEXT, ARCHITECTURE, EVENT_CATALOG, API_SPEC, and PLAN documentation to reflect the new active phase.

### Out of Scope

- Full commercial SaaS landing page.
- Billing, plans, subscriptions, or pricing.
- Real SAP, Oracle, AS400, or third-party ERP integration.
- Fully autonomous AI agents that mutate state without human approval.
- Full 3D physics simulation.
- Robot orchestration.
- Procurement, supplier scoring, or forecasting beyond optional demo read models.
- Production-grade multi-tenant isolation.

---

## Target Runtime Surface

### New App: `apps/mission-control`

Purpose:

- act as the single entry point for the portfolio demo
- orchestrate scenario triggers
- display compact live panels from the three existing surfaces
- explain the architecture as it runs
- expose reset/isolation status

Recommended layout:

```text
+----------------------------------------------------------------------------------+
| NEXUSWMS — AI SOLUTIONS ENGINEERING MISSION CONTROL                              |
| Modular Monolith • Transactional Outbox • Reverb • Offline Field UX • Decision AI |
+-------------------------------+--------------------------------------------------+
| Scenario Control              | Orchestrator Twin Snapshot                       |
|                               |                                                  |
| [Run Industrial Stress]       | 2.5D warehouse grid                              |
| [Trigger Critical Incident]   | blocked / congested / quarantined zones         |
| [Replay Legacy ERP Import]    | DecisionTrace overlay                            |
| [Reset Demo Session]          |                                                  |
+-------------------------------+--------------------------------------------------+
| Field-Agent Mobile Snapshot   | Vapor Monitor Telemetry                          |
|                               |                                                  |
| offline toggle                | raw event stream                                 |
| draft queue indicator         | KPI deltas                                       |
| movement / incident form      | drift / burst diagnostics                        |
+-------------------------------+--------------------------------------------------+
| Architecture Narrative Strip: current event chain, correlationId, causationId     |
+----------------------------------------------------------------------------------+
```

Implementation rule:

`apps/mission-control` must consume shared contracts and real APIs. It may render compact demo panels, but it must not duplicate canonical business rules from `apps/api` or reinterpret domain ownership.

---

## Phase 5 Execution Plan

## Phase 5.0 — Documentation Transition & Scope Lock

**Type:** Governance / Planning

### Objective

Replace the completed historical plan with this active plan and prevent agents from continuing to optimize completed phases as if they were unfinished.

### Tasks

- [x] Archive the old completed plan under a historical filename or docs archive.
- [x] Set this file as the new active `PLAN.md`.
- [x] Update `CONTEXT.md` current stage to `Phase 5 / Mission Control & Enterprise Solutions Engineering Demo`.
- [x] Add explicit note that Phases 0–4.5 are baseline and should not be rebuilt.
- [x] Add Phase 5 scope boundaries to AI governance docs.
- [x] Add a short `docs/FLOWS/demo-scenario-flow.md` describing the public demo flow.

### Exit Criteria

- [x] AI assistants understand that Phase 5 is the only active implementation phase.
- [x] Documentation does not imply that already completed modules need to be rebuilt.
- [x] Every new Phase 5 capability has an owner and a bounded scope.

---

## Phase 5.1 — Demo Session Isolation & Reset Mechanics

**Type:** Backend Infrastructure / Portfolio Safety

### Objective

Ensure public demo usage cannot corrupt the baseline operational dataset or pollute global metrics with endless synthetic events.

### Preferred MVP Strategy

Use a pragmatic `simulationRunId` and reset/purge approach instead of complex per-user ephemeral databases.

### Tasks

- [ ] Introduce a `simulationRunId` or equivalent demo context marker for generated demo events, decision traces, and audit records where appropriate.
- [ ] Add `php artisan nexus:demo-reset` to restore the demo dataset to a known baseline.
- [ ] Add `php artisan nexus:purge-simulations` to remove expired simulation records.
- [ ] Add seed data for a stable warehouse demo baseline: products, stock, locations, incidents, and movement history.
- [ ] Add a backend endpoint for resetting the current demo session if safe for the deployment environment.
- [ ] Show demo isolation status in Mission Control.

### Guardrails

- Do not delete non-demo records.
- Do not purge records without a clear simulation/session marker.
- Do not break audit/event append-only principles for production-like records; demo reset must be clearly scoped to demo data.

### Exit Criteria

- [ ] Running the public demo repeatedly does not permanently degrade the dataset.
- [ ] A reset returns the system to a known baseline.
- [ ] The UI clearly communicates whether the visitor is operating in demo mode.

---

## Phase 5.2 — Scenario Engine & Industrial Stress Injector

**Type:** Backend Application Layer / Simulation

### Objective

Create controlled scenario execution that generates realistic warehouse pressure without bypassing domain logic.

### Scenario Types

1. **Industrial Stress Burst**
   - Simulate multiple operators generating movements and incidents.
   - Exercise idempotency, outbox dispatch, drift detection, and burst stability.

2. **Critical Aisle Incident**
   - Create a high-severity incident affecting a known location/aisle.
   - Emit normal incident and inventory impact events through existing domain services.
   - Generate or surface DecisionTrace output as advisory intelligence.

3. **Offline Field Recovery**
   - Demonstrate local draft persistence in Field-Agent Mobile.
   - Reconnect and replay only through valid sync/command boundaries.

4. **Legacy ERP Import**
   - Import synthetic CSV/XML-style records.
   - Validate, reject, normalize, and transform accepted records into explicit domain commands/events.

### Tasks

- [ ] Add `DemoScenario` definitions under a clear backend owner, preferably a `Simulation` or `Demo` module.
- [ ] Implement a `ScenarioRunner` application service.
- [ ] Add `php artisan nexus:inject-stress --scenario=<name> --count=<n>`.
- [ ] Add API endpoint `POST /api/demo/scenarios/{scenario}/run` for Mission Control.
- [ ] Ensure scenario actions call existing domain services instead of writing directly to tables.
- [ ] Add correlation IDs to all events produced by a scenario run.
- [ ] Return a scenario execution summary with counts, created IDs, warnings, and correlation ID.

### Exit Criteria

- [ ] A scenario produces visible events across Vapor Monitor and Orchestrator Twin.
- [ ] Scenario execution is repeatable and bounded.
- [ ] No scenario bypasses authorization, validation, audit, or event rules.

---

## Phase 5.3 — Legacy ERP Sync Adapter Simulator

**Type:** Backend Integration / Enterprise Storytelling

### Objective

Prove that NexusWMS can sit between old enterprise data formats and modern event-driven operational workflows.

### MVP Behavior

This is not a real ERP connector. It is a deterministic adapter simulator that ingests messy synthetic files and shows how enterprise integration should be validated, normalized, rejected, and audited.

### Tasks

- [ ] Add sample files under `apps/api/database/demo/legacy-imports/` or `docs/samples/legacy-imports/`.
- [ ] Define `LegacyInboundRecord` DTO.
- [ ] Implement parser for CSV first; XML can be added only if CSV is complete.
- [ ] Normalize product, quantity, location, lot, and reference fields.
- [ ] Reject malformed records with structured errors.
- [ ] Transform accepted records into existing movement/receipt command flow.
- [ ] Emit normal `movement.created` and `inventory.stock.received` events through the current outbox path.
- [ ] Add import summary endpoint or command output.
- [ ] Display import results in Mission Control.

### Exit Criteria

- [ ] A legacy import can create valid inbound movements.
- [ ] Bad rows are rejected without partial corruption.
- [ ] Import activity is traceable by correlation ID.
- [ ] The UI clearly labels this as a simulated legacy adapter.

---

## Phase 5.4 — Mission Control Unified Demo Surface

**Type:** Frontend Product Surface

### Objective

Create one entry point that lets the visitor see the full system react without opening three browser tabs.

### Tasks

- [ ] Scaffold `apps/mission-control` using the existing monorepo conventions.
- [ ] Reuse `packages/shared-types`, `packages/shared-schemas`, `packages/event-contracts`, and `packages/ui-tokens`.
- [ ] Add a scenario control panel.
- [ ] Add compact Vapor Monitor telemetry panel.
- [ ] Add compact Orchestrator Twin spatial panel.
- [ ] Add compact Field-Agent Mobile panel or guided link to the full app when embedding would create excessive coupling.
- [ ] Add architecture narrative strip showing current event chain.
- [ ] Add `correlationId` and `causationId` visibility for the active scenario.
- [ ] Add DecisionTrace preview with status controls.
- [ ] Add demo reset/isolation indicator.
- [ ] Add responsive behavior for laptop screens.

### Frontend Guardrails

- Do not duplicate backend business logic.
- Do not mutate cross-domain state inside components.
- Do not create a massive global store.
- Keep panels projection-only unless executing an explicit API command.
- Keep domain folders aligned with the existing frontend architecture.

### Exit Criteria

- [ ] A visitor can run the main demo from one screen.
- [ ] The event chain is visible without opening developer tools.
- [ ] Field, monitor, twin, and decision trace panels feel like one system.
- [ ] The page reads as engineering infrastructure, not a marketing landing page.

---

## Phase 5.5 — Human-Approved Mitigation Flow

**Type:** Governed Intelligence / Backend + Frontend

### Objective

Upgrade the demo from “AI detected something” to “AI suggested an action, a human approved it, and the system executed it safely.”

### Flow

```text
Critical incident occurs
  -> event emitted
  -> DecisionTrace created
  -> Mission Control highlights suggestion
  -> user clicks Acknowledge or Apply Mitigation
  -> backend validates actor and action
  -> domain command executes
  -> audit log records action
  -> event emitted
  -> monitor and twin update
```

### MVP Mitigation Actions

Choose one or two only:

- block a warehouse location
- create an adjustment movement with a controlled reason such as `quality_hold`
- mark a decision trace as `acted_upon`

### Tasks

- [ ] Define allowed mitigation actions in documentation.
- [ ] Ensure every mitigation action maps to an existing authorized API command.
- [ ] Add UI action controls in Mission Control.
- [ ] Add clear “human-approved” language in the UI.
- [ ] Ensure action result is audited and linked to the original decision trace.
- [ ] Add tests proving agents do not mutate state directly.

### Exit Criteria

- [ ] The demo shows governed AI, not uncontrolled automation.
- [ ] Every mitigation has actor attribution.
- [ ] DecisionTrace status transitions are visible and auditable.
- [ ] Security constraints remain intact.

---

## Phase 5.6 — Engineering Insights Mode

**Type:** Portfolio Storytelling / UX Layer

### Objective

Make the project understandable to evaluators without requiring them to read every documentation file first.

### Tasks

- [ ] Add an `Engineering Insights` toggle in Mission Control.
- [ ] Add hover/click explanations for the most important architectural decisions.
- [ ] Explain the following concepts inline:
  - Modular Monolith
  - Transactional Outbox
  - Reverb WebSockets
  - Event Interpretation
  - Drift Detection
  - Decision Trace
  - IndexedDB Offline Drafts
  - Idempotency Key
  - Legacy Adapter Simulator
  - Demo Session Isolation
- [ ] Add short architecture callouts, not long paragraphs.
- [ ] Add links to relevant docs for deeper review.

### Exit Criteria

- [ ] A technical reviewer can understand why each architectural choice exists.
- [ ] The UI demonstrates seniority without becoming text-heavy.
- [ ] The experience feels like a living architecture whitepaper.

---

## Phase 5.7 — Demo Hardening, QA, and Portfolio Packaging

**Type:** QA / Release / Portfolio

### Objective

Make the project safe to share publicly.

### Tasks

- [ ] Add browser walkthrough test for the main Mission Control scenario.
- [ ] Add backend tests for scenario execution, reset/purge, and legacy import validation.
- [ ] Add tests proving scenario-generated events preserve canonical contracts.
- [ ] Add tests proving duplicate scenario execution does not corrupt state.
- [ ] Add accessibility checks for Mission Control critical controls.
- [ ] Add failure states: API down, Reverb disconnected, scenario failed, reset failed.
- [ ] Add a short demo video script.
- [ ] Update README with a “Run the 5-minute demo” section.
- [ ] Add architecture diagram for the Phase 5 flow.
- [ ] Add screenshots/GIF placeholders for portfolio presentation.

### Exit Criteria

- [ ] The demo can be run by someone who has never seen the repo.
- [ ] The main scenario is stable after multiple runs.
- [ ] The README explains what to look at, why it matters, and how to verify it.
- [ ] The portfolio story is clear: field execution → event backbone → telemetry → spatial intelligence → governed AI.

---

## Primary Demo Script

This is the ideal flow for a recruiter or technical evaluator.

### Step 1 — Enter Mission Control

The visitor sees a dense but clear operational control panel. The system is calm. Baseline inventory, warehouse layout, telemetry, and decision trace panels are visible.

### Step 2 — Run Industrial Stress

The visitor clicks:

```text
Run Industrial Stress
```

Expected behavior:

- backend executes a bounded scenario
- events flow through the outbox and Reverb
- telemetry panel shows event bursts
- KPI deltas update
- drift/burst diagnostics remain stable

### Step 3 — Trigger Critical Incident

The visitor clicks:

```text
Trigger Critical Aisle Incident
```

Expected behavior:

- a real incident command is executed through the API
- an `incident.reported` event is emitted
- affected location/zone is highlighted in the 2.5D view
- DecisionTrace appears with detection, reasoning, severity, and suggestion

### Step 4 — Approve Mitigation

The visitor clicks:

```text
Apply Human-Approved Mitigation
```

Expected behavior:

- backend validates the action
- location block or quality hold movement is executed through proper domain services
- audit record is created
- events are emitted
- Mission Control shows the resulting event chain
- DecisionTrace status changes to `acted_upon`

### Step 5 — Replay Legacy Import

The visitor clicks:

```text
Replay Legacy ERP Import
```

Expected behavior:

- valid rows become domain commands
- invalid rows are rejected with clear reasons
- accepted records emit normal operational events
- import summary appears in the UI

### Step 6 — Reset Demo

The visitor clicks:

```text
Reset Demo Session
```

Expected behavior:

- demo state returns to baseline
- synthetic scenario records are cleared or re-scoped
- system is ready for the next visitor

---

## Success Criteria for Phase 5

Phase 5 is complete when:

- [ ] Mission Control exists as a single entry point.
- [ ] The main demo can be understood in under 30 seconds.
- [ ] The main demo can be completed in under 5 minutes.
- [ ] Stress scenarios generate real backend events through valid domain paths.
- [ ] Realtime updates appear across telemetry and spatial panels.
- [ ] Decision traces remain advisory and explainable.
- [ ] Human-approved mitigation is audited and event-backed.
- [ ] Legacy adapter simulator demonstrates enterprise integration thinking.
- [ ] Demo reset/isolation prevents public data pollution.
- [ ] Documentation and code stay aligned.
- [ ] Tests validate the demo path.
- [ ] README explains the business and engineering value clearly.

---

## What This Phase Must Prove

By the end of Phase 5, NexusWMS should communicate the following senior-level signals:

- ability to work with existing systems instead of rebuilding everything
- ability to integrate frontend, backend, realtime, offline, and AI layers
- ability to preserve domain invariants under pressure
- ability to design for auditability and traceability
- ability to make AI useful without making it unsafe
- ability to build demos that reveal engineering decisions, not just UI polish
- ability to think like an AI Solutions Engineer for traditional industries

---

## Implementation Order

Recommended execution order:

1. Phase 5.0 — Documentation Transition & Scope Lock
2. Phase 5.1 — Demo Session Isolation & Reset Mechanics
3. Phase 5.2 — Scenario Engine & Industrial Stress Injector
4. Phase 5.3 — Legacy ERP Sync Adapter Simulator
5. Phase 5.4 — Mission Control Unified Demo Surface
6. Phase 5.5 — Human-Approved Mitigation Flow
7. Phase 5.6 — Engineering Insights Mode
8. Phase 5.7 — Demo Hardening, QA, and Portfolio Packaging

Do not start visual polish before scenario execution, reset mechanics, and governance boundaries are stable.

---

## Documentation Updates Required

When Phase 5 work starts, update these files:

- `PLAN.md` — replace old active plan with this Phase 5 plan.
- `CONTEXT.md` — current stage and active mission.
- `ARCHITECTURE.md` — add Mission Control and demo scenario architecture.
- `API_SPEC.md` — add demo/scenario endpoints only after implementation.
- `EVENT_CATALOG.md` — add no new events unless truly needed; prefer existing domain events.
- `DATA_DICTIONARY.md` — add `SimulationRun`, `DemoScenario`, or `LegacyInboundRecord` only if implemented.
- `SECURITY_MODEL.md` — clarify human-approved mitigation and demo action authorization.
- `README.md` — add portfolio walkthrough and 5-minute demo path.

---

## Final Rule

Phase 5 must not make NexusWMS bigger for the sake of looking bigger.

Phase 5 must make the existing system easier to understand, harder to dismiss, safer to demo publicly, and stronger as evidence of real AI Solutions Engineering capability.
