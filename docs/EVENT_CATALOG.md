# NexusWMS Event Catalog

## Purpose

This document defines the domain events of NexusWMS.

Events represent meaningful changes in the system.
They are used for:

- auditability
- observability
- future realtime updates
- AI monitoring and decision-making
- digital twin simulation

Events are immutable and represent facts that already happened.

---

## Event Principles

- Events are emitted after successful operations
- Events must be descriptive and explicit
- Events must include enough data to be understood independently
- Events are append-only (never updated)
- Events should not contain sensitive data

---

## Event Structure

All events follow this structure:

```json
{
  "eventId": "evt_001",
  "eventType": "string",
  "occurredAt": "ISO-8601 timestamp",
  "actorId": "user_001",
  "payload": {},
  "eventVersion": 1
}
```

---

---

## Inventory Events

### inventory.stock.adjusted

Triggered when stock quantity is modified.

**Source**:

- `POST /api/movements` (type: `adjustment`)

**Payload**:

```json
{
  "productId": "prod_001",
  "locationId": "loc_001",
  "previousQuantity": 100,
  "newQuantity": 95,
  "reason": "manual_adjustment"
}
```

---

### inventory.stock.relocated

Triggered when stock is moved between locations.

**Source**:

- `POST /api/movements` (type: `relocation`)

**Payload**:

```json
{
  "productId": "prod_001",
  "fromLocationId": "loc_001",
  "toLocationId": "loc_002",
  "quantity": 5
}
```

---

### inventory.stock.received

Triggered when new stock enters the system.

**Source**:

- `POST /api/movements` (type: `receipt`)

**Payload**:

```json
{
  "productId": "prod_001",
  "locationId": "loc_001",
  "quantity": 100,
  "lotNumber": "LOT-2026-001"
}
```

---

### inventory.stock.picked

Triggered when stock is consumed (e.g. picking).

**Source**:

- `POST /api/movements` (type: `picking`)

**Payload**:

```json
{
  "productId": "prod_001",
  "locationId": "loc_001",
  "quantity": 3
}
```

---

## Incident Events

### incident.reported

Triggered when a new incident is created.

**Source**:

- `POST /api/incidents`

**Payload**:

```json
{
  "incidentId": "inc_001",
  "productId": "prod_001",
  "locationId": "loc_001",
  "type": "damage",
  "description": "Outer package is broken"
}
```

---

### incident.status.updated

Triggered when incident status changes.

**Source**:

- `PATCH /api/incidents/{id}/status`

**Payload**:

```json
{
  "incidentId": "inc_001",
  "previousStatus": "open",
  "newStatus": "resolved"
}
```

---

## Movement Events

### movement.created

Triggered when a movement is registered.

**Source**:

- `POST /api/movements`

**Payload**:

```json
{
  "movementId": "mov_001",
  "productId": "prod_001",
  "type": "relocation",
  "quantity": 5,
  "fromLocationId": "loc_001",
  "toLocationId": "loc_002"
}
```

---

## Location Events

### location.blocked

Triggered when a location becomes blocked.

**Payload**:

```json
{
  "locationId": "loc_001",
  "reason": "maintenance"
}
```

---

### location.unblocked

Triggered when a location becomes available again.

**Payload**:

```json
{
  "locationId": "loc_001"
}
```

---

## Product Events

### product.created

Triggered when a new product is created.

**Payload**:

```json
{
  "productId": "prod_001",
  "sku": "TV-001",
  "name": "Televisor Samsung 55"
}
```

---

## Event Consumers (Future)

These events may be consumed by:

1. **Audit System**: Persists all events for traceability.
2. **Realtime UI**: Updates dashboards and views.
3. **AI Monitoring Agent**: Detects anomalies and suggests actions.
4. **Digital Twin**: Simulates warehouse state.
5. **Analytics / BI**: Builds reports and KPIs.

---

## MVP Scope

For the initial version:

- events are emitted internally (not external messaging yet)
- no event bus required
- no Kafka / queues required
- events can be stored in a simple event log table

Future versions may introduce:

- message broker (Kafka, RabbitMQ)
- event streaming
- distributed consumers
- event replay

---

## Full Event Example

Example of a fully emitted event:

```json
{
  "eventId": "evt_001",
  "eventType": "inventory.stock.adjusted",
  "eventVersion": 1,
  "occurredAt": "2026-01-01T10:00:00Z",
  "actorId": "user_001",
  "correlationId": "req_123",
  "causationId": "evt_000",
  "payload": {
    "productId": "prod_001",
    "productName": "Samsung TV 55",
    "sku": "TV-001",
    "locationId": "loc_001",
    "previousQuantity": 100,
    "newQuantity": 95,
    "reason": "manual_adjustment",
    "unit": "units"
  }
}
```
