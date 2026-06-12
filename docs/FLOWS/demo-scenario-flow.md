# Public Demo Flow

## Purpose

This document describes the intended Phase 5 demo flow. It illustrates how the NexusWMS portfolio architecture reacts to a scenario trigger and orchestrates actions across its integrated surfaces. It is a design document outlining the _intended_ flow. Mission Control and scenario execution are intended Phase 5 capabilities, not claims of completed runtime behavior.

## Scope

- Planned: Unified Mission Control demo surface
- Planned: Controlled scenario execution and isolation
- Planned: Legacy ERP import simulation
- Phase 5 target: Governed AI decision trace and human mitigation
- Phase 5 target: Real-time monitoring and twin spatial updates

## Out-of-scope items

- Real production integrations (SAP, Oracle, AS400, etc.)
- Fully autonomous AI mutating state without human approval
- Full SaaS landing pages or commercial flows
- 3D Physics simulation
- Procurement or forecasting beyond demo scenarios

## Actors

- **Operator / Demo Visitor:** In the Phase 5 target flow, triggers scenarios through Mission Control or an existing operational surface.
- **API Domain Logic:** Validates, processes, and persists actions once the relevant Phase 5 capabilities are implemented.
- **Decision Agent:** Analyzes events and produces advisory DecisionTraces.
- **Human Approver:** Reviews suggested mitigations and explicitly approves any state-changing action.

## High-level flow

The flow illustrates how an external trigger drives the event pipeline and eventually leads to a governed mitigation response.

## Detailed flow

```text
Scenario Trigger
  -> Field / Legacy / Demo Input
  -> Laravel Domain Logic
  -> Transactional Outbox
  -> Reverb Broadcast
  -> Vapor Monitor Telemetry
  -> Orchestrator Twin Spatial Update
  -> DecisionTrace Explanation
  -> Human-Approved Mitigation
  -> Authorized Backend Command
  -> Audit Log
  -> Domain Event
  -> Demo Reset / Isolation Boundary
```

## Guardrails

- **Isolation:** Demo scenarios must be isolated and must not pollute the canonical operational dataset. They should be scoped to a session or simulation run.
- **Governance:** Agents are advisory only. Any domain state mutation (like inventory adjustment) must be executed explicitly via a human-approved API command.
- **Truthfulness:** Simulated ERP inputs and scenarios must be clearly labeled as simulation, not production features.
- **Documentation Truthfulness:** API endpoints, event contracts, and data dictionary terms must only be documented as implemented after the corresponding code exists.

## Traceability model

All actions and anomalies must retain full traceability. Events are linked via `correlationId` (for full operational workflows) and `causationId` (to attribute the direct preceding event). A `DecisionTrace` will carry these identifiers, ensuring that any human-approved mitigation is directly linked back to its originating trigger.

## Exit criteria

- The intended bounded scenario flow is documented from trigger to reset.
- Demo isolation requirements are clearly documented.
- AI recommendations remain suggestion-first until explicitly approved by a user.
- Documentation honestly reflects that this is an intended Phase 5 design and not a completed commercial/runtime feature.
