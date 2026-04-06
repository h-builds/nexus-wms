# NexusWMS AI Agents

## Purpose

This document defines the **Runtime AI Pipeline** that operates within NexusWMS (the resulting system).

> [!CAUTION]
> **This document does NOT govern Development Coding Assistants (like Cursor or Copilot).**
> If you are an AI reading this file while attempting to write source code (e.g., building a controller or a service), **IGNORE the "Agents DO NOT mutate state" rules below**. Those rules apply to the _operational monitoring AI microservices_ running inside the warehouse, not to you as a developer. Your coding rules are strictly contained in `.ai/RULES.md`.

Runtime Agents are not generic software assistants. They are domain-scoped warehouse operators that:

- observe production events
- analyze physical system state
- suggest operational actions (MVP)
- execute operational actions (future)
- respect governance constraints

All agents must comply with:

- `CONTEXT.md`
- `RULES.md`
- `DATA_GUARDRAILS.md`
- `EVALS.md`

---

## Agent Design Principles

### 1. Domain Alignment

Each agent must belong to a specific domain:

- Inventory
- Incidents
- Movements
- Monitoring
- Simulation (future)

> [!IMPORTANT]
> No cross-domain uncontrolled agents are permitted.

---

### 2. Event-Driven Thinking

Agents are conceptualized as reacting to:

- domain events
- state changes
- anomalies

**Event Consumption Guardrail (MVP):**
Agents must **NOT** directly read or poll the `event_outbox` database table. The outbox pattern guarantees transaction atomicity for the core system. Direct DB polling by AI scripts risks locking or skipping events.
During the MVP phase, Agents operate via explicit REST API reporting or process events pushed to them synchronously by the application layer. True asynchronous streaming ingestion (e.g., Kafka, RabbitMQ) is deferred to Phase 2.

---

### 3. Suggestion First (MVP)

In the MVP phase:

- Agents **DO NOT** mutate system state.
- Agents **DO NOT** execute commands automatically.

Agents focus on:

- analysis
- detecting anomalies
- suggesting actions

---

### 4. Explainability Required

## Agent Output Contract (DecisionTrace)

All persisted advisory outputs intended for operator visibility, follow-up, or audit MUST be recorded as a `DecisionTrace` record with the following required fields:

| Field             | Purpose                                                                                         |
| :---------------- | :---------------------------------------------------------------------------------------------- |
| `traceType`       | Classification: `anomaly_detection`, `pattern_insight`, `optimization_suggestion`, `risk_alert` |
| `agentId`         | Identifier of the producing agent                                                               |
| `agentDomain`     | Domain scope of the agent                                                                       |
| `detection`       | Human-readable summary of what was detected                                                     |
| `reasoning`       | Explanation of why the detection matters                                                        |
| `suggestion`      | Recommended action for the operator                                                             |
| `severity`        | `low`, `medium`, `high`, `critical`                                                             |
| `causationId`     | The `eventId` that directly triggered analysis                                                  |
| `correlationId`   | The originating correlation chain                                                               |
| `triggerEventIds` | All event IDs the agent considered                                                              |
| `status`          | Lifecycle state: `advisory` → `acknowledged` / `acted_upon` / `dismissed`                       |
| `createdAt`       | Timestamp of trace creation                                                                     |

**Optional (set during resolution)**:

| Field         | Purpose                      |
| :------------ | :--------------------------- |
| `actedUponAt` | When the trace was resolved  |
| `actedUponBy` | Actor who resolved the trace |

> [!IMPORTANT]
> Not all intermediate or low-signal agent outputs must be persisted. Only material, actionable, or audit-relevant advisory outputs should be recorded as DecisionTrace entities.

## See `docs/DOMAIN_MODEL.md` for full entity definition.

### 5. No Data Overreach

Agents must respect `DATA_GUARDRAILS.md`. They must not:

- expose sensitive data
- invent missing data
- use hidden context

---

## Agent Types

### 1. Inventory Monitoring Agent

- **Domain**: Inventory
- **Purpose**: Monitor stock levels and detect anomalies.
- **Inputs**:
  - `inventory.stock.adjusted`
  - `inventory.stock.received`
  - `inventory.stock.picked`
  - `inventory.stock.relocated`
  - `inventory.stock.putaway`
  - `inventory.stock.returned`
- **Responsibilities**:
  - detect unusual drops in stock
  - detect negative stock conditions
  - detect abnormal movement patterns
- **Outputs (MVP)**:
  - anomaly alerts
  - suggested investigation actions

**Example Output**:

- "Stock for SKU TV-001 dropped 40% in 2 hours"
- "Suggested action: review recent movements for this SKU"

---

### 2. Incident Analysis Agent

- **Domain**: Incidents
- **Purpose**: Analyze incidents and detect patterns.
- **Inputs**:
  - `incident.reported`
  - `incident.status.updated`
- **Responsibilities**:
  - detect recurring issues by product
  - detect problematic locations
  - identify high-frequency incident types
- **Outputs (MVP)**:
  - pattern insights
  - suggested root cause investigations

**Example Output**:

- "Product TV-001 has 5 damage incidents in 24h"
- "Suggested action: inspect packaging process"

---

### 3. Movement Intelligence Agent

- **Domain**: Movements
- **Purpose**: Analyze stock movements for inefficiencies or anomalies.
- **Inputs**:
  - `movement.created`
- **Responsibilities**:
  - detect excessive relocations
  - detect inefficient movement patterns
  - detect redundant operations
- **Outputs (MVP)**:
  - optimization suggestions
  - anomaly alerts

---

### 5. Audit Support Agent (Future-Oriented)

- **Domain**: Audit
- **Purpose**: Support traceability and investigation.
- **Inputs**:
  - all events
  - audit logs (future)
- **Responsibilities**:
  - reconstruct event timelines
  - explain how a state was reached
  - assist audits and investigations
- **Outputs**:
  - trace narratives
  - investigation summaries

---

## Agent Interaction Model (MVP)

User Action → API → Domain Logic → Event Emitted → Agent Consumes → Suggestion Generated → UI Displays Insight

**Agents do not**:

- call APIs
- mutate state
- bypass backend logic

---

## Future Evolution (Post-MVP)

Agents may evolve to:

- execute approved actions
- trigger workflows
- orchestrate multi-step operations
- simulate scenarios (digital twin)
- optimize inventory placement
- predict stock shortages

**This requires**:

- explicit authorization model
- stronger audit system
- decision trace storage

---

## Guardrails for All Agents

> [!CAUTION]
> Agents must **NEVER**:
>
> - mutate inventory directly
> - bypass domain services
> - invent system data
> - fabricate events
> - expose sensitive information
> - assume features that are not implemented

---

## Agent Output Contract (DecisionTrace)

Every agent output is persisted as a `DecisionTrace` record with the following required fields:

| Field             | Purpose                                                                                         |
| :---------------- | :---------------------------------------------------------------------------------------------- |
| `traceType`       | Classification: `anomaly_detection`, `pattern_insight`, `optimization_suggestion`, `risk_alert` |
| `agentId`         | Identifier of the producing agent                                                               |
| `agentDomain`     | Domain scope of the agent                                                                       |
| `detection`       | Human-readable summary of what was detected                                                     |
| `reasoning`       | Explanation of why the detection matters                                                        |
| `suggestion`      | Recommended action for the operator                                                             |
| `severity`        | `low`, `medium`, `high`, `critical`                                                             |
| `causationId`     | The `eventId` that directly triggered analysis                                                  |
| `correlationId`   | The originating correlation chain                                                               |
| `triggerEventIds` | All event IDs the agent considered                                                              |
| `status`          | Lifecycle state: `advisory` → `acknowledged` / `acted_upon` / `dismissed`                       |
| `createdAt`       | Timestamp of trace creation                                                                     |

**Optional (set during resolution)**:

| Field         | Purpose                      |
| :------------ | :--------------------------- |
| `actedUponAt` | When the trace was resolved  |
| `actedUponBy` | Actor who resolved the trace |

See `docs/DOMAIN_MODEL.md` for full entity definition.

---

## Example Agent Output

- **Observation**: Stock for SKU TV-001 dropped significantly.
- **Context**:
  - Previous quantity: 100
  - Current quantity: 60
  - Time window: 2 hours
- **Reasoning**: Drop exceeds normal movement patterns.
- **Suggested Action**: Review recent picking and relocation movements.

---

## Integration Points

Agents will eventually integrate with:

- event stream
- monitoring dashboards (Vapor Monitor)
- orchestration layer (Orchestrator Twin)
- future AI execution engine
- Intelligence domain (decision trace persistence and query)

## Final Rule

> [!IMPORTANT]
> Agents are assistants to the system, not replacements for domain logic.

**They must**:

- observe
- interpret
- suggest

**They must not**:

- override
- shortcut
- corrupt system behavior

---

Context loading rules are defined in `.ai/CONTEXT_LOADING.md`.
Agents must never load full repository context by default.
