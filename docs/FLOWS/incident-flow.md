# Incident Flow

## Purpose

This flow defines how incidents are:

- **Created**: Initial report from field or system.
- **Classified**: Categorization and severity assessment.
- **Investigated**: Detailed analysis of the incident.
- **Resolved**: Corrective actions and closure.
- **Audited**: Traceability of all state changes.

It ensures traceability, operational clarity, and AI-safe handling of incident data.

---

## Scope

### Domain(s) Involved
- **Incidents**: Owns the incident lifecycle.
- **Inventory**: Manages stock impacts.
- **Locations**: Provides spatial context.
- **Audit**: Tracks system-wide traceability.
- **Events**: Handles asynchronous communication.

### Actors
- **Field Agent (mobile)**: Reports incidents from the warehouse floor.
- **Warehouse Operator**: Investigates and resolves incidents.
- **System (AI classification)**: Categorizes and assesses severity.
- **Supervisor**: Optional reviewer for high-severity cases.

---

## High-Level Flow

1. **Report**: Incident is reported via mobile or web.
2. **Validate**: System validates inputs and AI classifies.
3. **Persist**: Incident is recorded in the system of record.
4. **Impact**: Inventory impact is evaluated and blocked if needed.
5. **Emit**: Domain events are emitted for downstream consumers.
6. **Resolve**: Incident is investigated and moved to a final state.
7. **Audit**: Complete history is preserved for compliance.

---

## Detailed Flow

### 1. Incident Creation

**Source:**
- Mobile App (Field Agent)
- Web Dashboard
- System-generated anomaly (Future)

**Request:**
```http
POST /api/incidents
Idempotency-Key: req_123_uuid_here

{
  "type": "damage",
  "severity": "medium",
  "description": "Box crushed during unloading",
  "locationId": "loc_001",
  "productId": "prod_001",
  "quantityAffected": 5
}
```

**Validations:**
- Location must exist and be active.
- `productId` must exist in the product catalog.
- Quantity must be greater than zero.
- Description must be sanitized (Prompt Injection Protection).

**Duplicate detection:**

Before creating a new incident, the system checks for an existing `open` incident with the same `productId`, `locationId`, and `type` created within the last 15 minutes. If found:
- The request is rejected with `409 Conflict`.
- The response body includes the existing incident ID.
- This prevents double-reporting of the same physical damage by different operators.

> [!NOTE]
> The 15-minute window is configurable. It is intentionally short to avoid blocking legitimate reports of different incidents at the same location.

---

### 2. AI Classification (Advisory)

The system performs an automated analysis to assist the operator:

- **Normalize**: Standardizes the text description.
- **Classify**: Assigns a severity level (Low, Medium, High).
- **Categorize**: Suggests the best-fit incident category.

**Example Output:**
```json
{
  "normalizedType": "damage",
  "severity": "medium",
  "confidence": 0.87
}
```

> [!IMPORTANT]
> AI output is advisory only. Final persisted values must be validated by business rules, and no AI-generated text is trusted without sanitization.

---

### 3. Incident Persistence

**Entity:** `InventoryIncident`

**Fields:**
- `id`: Unique identifier (UUID).
- `type`: Category of the incident.
- `severity`: Severity level (low, medium, high).
- `description`: Human-readable summary.
- `locationId`: Reference to the warehouse location.
- `productId`: Reference to the affected product.
- `quantityAffected`: Units involved.
- `status`: Current state (`open` | `in_review` | `resolved` | `closed`).
- `createdAt`: Timestamp.
- `createdBy`: Actor identification (server-set from authenticated session).

---

### 4. Inventory Impact

If the incident affects physical stock, the system must trigger inventory changes **through the Movements domain**, not by direct mutation.

> [!CAUTION]
> Inventory is never mutated directly by the Incident domain. This is a core system invariant (DOMAIN_MODEL rule 3, RULES.md Rule 2). All stock changes must flow through the Movements domain to preserve traceability and auditability.

> [!CAUTION]
> **Transaction boundary**: The incident persistence (step 3) and the adjustment movement creation (this step) must occur within a **single database transaction**. If the adjustment fails, the incident must not persist. This is architecturally valid because NexusWMS is a modular monolith with a shared database.

**Process:**

1. Incident is persisted with `quantityAffected`.
2. If stock blocking is required, the system creates an `adjustment` movement:
   - `productId`: from the incident
   - `fromLocationId`: from the incident
   - `type`: `adjustment`
   - `quantity`: min(`quantityAffected`, `quantityAvailable`) — partial blocking if insufficient
   - `reason`: `incident_damage` (or matching incident type)
   - `reference`: `incident:{incidentId}`
3. The adjustment movement updates `quantityBlocked` and `quantityAvailable` through the Inventory domain's normal mutation path.
4. Both `movement.created` and `inventory.stock.adjusted` events are written to the event outbox within the same transaction.
5. If no available stock exists at the location, the incident persists with stock impact = 0 and a flag indicating no blocking was possible.

**Edge case: `quantityAffected` > `quantityAvailable`:**

The incident is always persisted (reporting an anomaly is valid regardless of stock levels). The adjustment blocks only up to `quantityAvailable`. The incident record retains the full `quantityAffected`. The difference is visible in audit and signals a potential pre-existing inventory discrepancy.

**Validation:**
- Must maintain total stock consistency (`quantityAvailable + quantityBlocked = quantityOnHand`).
- Stock consistency is enforced by database CHECK constraints as a safety net.

---

### 5. Event Emission

After successful persistence, the following events are emitted:

**Event: `incident.reported`**
```json
{
  "eventId": "evt_001",
  "eventType": "incident.reported",
  "eventVersion": 1,
  "occurredAt": "2026-03-27T12:30:00Z",
  "actorId": "user_001",
  "correlationId": "req_123",
  "causationId": "req_123",
  "payload": {
    "incidentId": "inc_001",
    "type": "damage",
    "severity": "medium",
    "locationId": "loc_001",
    "productId": "prod_001",
    "quantityAffected": 5
  }
}
```

**Event: `inventory.stock.adjusted`** (emitted by the Movements domain if stock blocking was triggered)
```json
{
  "eventId": "evt_002",
  "eventType": "inventory.stock.adjusted",
  "eventVersion": 1,
  "occurredAt": "2026-03-27T12:30:01Z",
  "actorId": "user_001",
  "correlationId": "req_123",
  "causationId": "evt_001",
  "payload": {
    "productId": "prod_001",
    "locationId": "loc_001",
    "previousQuantity": 100,
    "newQuantity": 95,
    "reason": "incident_damage"
  }
}
```

> [!NOTE]
> Events are immutable facts emitted only after the transaction is successfully committed to the database. The `inventory.stock.adjusted` event is emitted by the Movements domain, not the Incidents domain.

---

### 6. Investigation Phase

**Status Update:**
`PATCH /api/incidents/{id}/status`

- Sets status to `in_review`.

**Metadata Update:**
`PATCH /api/incidents/{id}`

**Allowed Operations:**
- Add internal `notes`.
- Reassign to a different operator.

**Constraints:**
- No silent mutation of original report data (`type`, `description`, `productId`, `locationId`).
- Full version history must be preserved in the audit log.

---

### 7. Resolution

**Final State:** `resolved`

**Resolution Actions:**
- **Return to Stock**: If items were wrongly reported or fixed.
- **Discard Damaged**: Permanent removal from inventory.
- **Replenish**: Trigger replenishment flow (Future Feature).

---

### 8. Audit Trail

Each change must be traceable to ensure compliance:

- **Who**: Actor ID (Human or System).
- **What**: JSON diff of modified attributes.
- **When**: High-precision timestamp.
- **Where**: API endpoint or background job name.

Audit is mandatory for all state-changing operations.

---

## AI Execution Guardrails

### Prompt Injection Protection
- **Untrusted Fields**: Description and notes.
- **Sanitization**: Strip control characters and HTML.
- **Context Limitation**: Never use untrusted fields as instructions for the LLM.

### AI Usage Rules
- **Classification**: AI can suggest categories and severity.
- **Summarization**: AI can provide a "TL;DR" for long incident notes.
- **Safety**: AI **cannot** override validation rules or modify stock directly.

---

## Failure Scenarios

- **Invalid Location**: Request rejected (422 Unprocessable Content).
- **Invalid SKU**: Request rejected (422 Unprocessable Content).
- **Negative Stock Risk**: Inventory adjustment blocked to prevent data corruption.
- **AI Failure**: Fallback to manual classification without blocking the flow.

---

## Observability & Metrics

**KPs to Track:**
- Incident creation rate per location.
- Type distribution (Damage vs. Loss vs. Anomaly).
- Mean Time to Resolution (MTTR).
- Total stock value impacted by incidents.

---

## Future Extensions

- Image-based damage detection using mobile vision.
- Anomaly detection triggered by digital twin pattern mismatches.
- Automated replenishment triggers for high-turnover SKUs.
- Predictive incident prevention using historical data.

---

## Summary

This flow ensures:
- **Operational Clarity**: Step-by-step lifecycle management.
- **Data Integrity**: Strict inventory and audit rules.
- **Traceability**: Every action is recorded and attributable.
- **AI Safety**: Robust guardrails against injection and hallucination.

---
