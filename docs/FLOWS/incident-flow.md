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
`POST /api/incidents`

**Payload:**
```json
{
  "type": "damage",
  "description": "Box crushed during unloading",
  "locationId": "loc_001",
  "sku": "TV-001",
  "quantityAffected": 5
}
```

**Validations:**
- Location must exist and be active.
- SKU must exist in the product catalog.
- Quantity must be greater than zero.
- Description must be sanitized (Prompt Injection Protection).

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

**Entity:** `Incident`

**Fields:**
- `id`: Unique identifier (UUID).
- `type`: Category of the incident.
- `description`: Human-readable summary.
- `locationId`: Reference to the warehouse location.
- `sku`: Reference to the affected product.
- `quantityAffected`: Units involved.
- `status`: Current state (`open` | `investigating` | `resolved`).
- `createdAt`: Timestamp.
- `createdBy`: Actor identification.

---

### 4. Inventory Impact

If the incident affects physical stock, the system must update inventory availability:

**Rules:**
- `quantityBlocked` += `quantityAffected`
- `quantityAvailable` -= `quantityAffected`

**Validation:**
- Cannot reduce available stock below zero.
- Must maintain total stock consistency (`available + blocked + reserved = total`).

---

### 5. Event Emission

After successful persistence, the following events are emitted:

**Event: `incident.created`**
```json
{
  "incidentId": "inc_001",
  "type": "damage",
  "locationId": "loc_001",
  "sku": "TV-001",
  "quantityAffected": 5
}
```

**Event: `inventory.stock.adjusted` (Optional)**
```json
{
  "productId": "prod_001",
  "sku": "TV-001",
  "locationId": "loc_001",
  "previousQuantity": 100,
  "newQuantity": 95,
  "reason": "incident_damage"
}
```

> [!NOTE]
> Events are immutable facts emitted only after the transaction is successfully committed to the database.

---

### 6. Investigation Phase

**Updates:**
`PATCH /api/incidents/{id}`

**Allowed Operations:**
- Update `status` to `investigating`.
- Add internal `notes`.
- Reassign to a different operator.

**Constraints:**
- No silent mutation of original report data.
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
