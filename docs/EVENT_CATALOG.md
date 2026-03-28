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
  "correlationId": "req_123",
  "causationId": "evt_000",
  "payload": {},
  "eventVersion": 1
}
```

> [!NOTE]
> `correlationId` links all events to the originating request. `causationId` links an event to the directly preceding event in a chain. Both are required for traceability.

---

## Event Versioning Policy

All events include an `eventVersion` field (integer, starting at 1).

When an event payload changes:

1. **Additive changes** (new optional fields): Do **not** increment the version. Consumers must tolerate unknown fields.
2. **Breaking changes** (field removed, field type changed, field renamed): Increment `eventVersion`. The system must emit **only the new version**. A migration window is not supported in the MVP.
3. **Semantic changes** (same field, different meaning): Treat as breaking. Increment version.

Consumers must:

- Ignore unknown fields in payloads.
- Reject events with an `eventVersion` higher than their supported version (log a warning, do not crash).
- Never assume payload shape without checking `eventVersion`.

---

## Event Emission Atomicity

> [!CAUTION]
> Events must be persisted in the **same database transaction** as the domain state change they describe.

The system uses the **Transactional Outbox** pattern:

1. Within the domain transaction, write the event to an `event_outbox` table.
2. After transaction commit, a background process (or synchronous dispatcher for MVP) reads from the outbox and delivers events to consumers.
3. Delivered events are marked as `dispatched`.

This guarantees:

- No event is emitted for a failed operation.
- No operation succeeds without its event being recorded.
- Multiple events from one operation (e.g., `movement.created` + `inventory.stock.relocated`) are atomically written together.

For the MVP, the outbox reader can be a synchronous after-commit hook. For production scaling, it should be a polling worker or CDC-based reader.

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
  "movementId": "mov_001",
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

### inventory.stock.putaway

Triggered when stock is placed into a storage location after receipt.

**Source**:

- `POST /api/movements` (type: `putaway`)

**Payload**:

```json
{
  "productId": "prod_001",
  "fromLocationId": "loc_staging",
  "toLocationId": "loc_001",
  "quantity": 100
}
```

---

### inventory.stock.returned

Triggered when stock is internally returned to inventory.

**Source**:

- `POST /api/movements` (type: `return_internal`)

**Payload**:

```json
{
  "productId": "prod_001",
  "fromLocationId": "loc_picking",
  "toLocationId": "loc_001",
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

Triggered when any movement is registered. This is a generic event emitted alongside the specific inventory event for the movement type.

> [!NOTE]
> `movement.created` is always emitted together with the matching `inventory.stock.*` event. Consumers should prefer the specific event unless they need to track all movement types generically.

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

**Source**:

- `PATCH /api/locations/{id}/status` (isBlocked: true)

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

**Source**:

- `PATCH /api/locations/{id}/status` (isBlocked: false)

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

**Source**:

- `POST /api/products`

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

## Event Retention & Archival

The event log table will grow continuously. The following strategy applies:

### Retention tiers

| Age | Storage | Access |
| :--- | :--- | :--- |
| 0–90 days | Primary database (hot) | Direct query |
| 90 days – 1 year | Archive table or cold storage (warm) | On-demand query |
| 1 year+ | Compressed export (cold) | Batch extraction only |

### Implementation guidance (MVP)

- Partition the event log table by `occurredAt` month (e.g., `event_log_2026_03`).
- Add a database index on `(occurredAt, eventType)` for time-range queries.
- Add a database index on `(correlationId)` for trace reconstruction.
- Implement a monthly cron job that moves events older than 90 days to an archive table.

### Storage estimates

At ~400 events/day (medium warehouse):

- 1 month: ~12,000 events
- 1 year: ~146,000 events
- 5 years: ~730,000 events

With average payload of 500 bytes per event, 5-year storage is ~365 MB — manageable but requires archival to maintain query performance on the hot table.

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
