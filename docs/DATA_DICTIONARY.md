# NexusWMS Data Dictionary

## Purpose

This file defines the controlled vocabulary for the project.
It exists to reduce ambiguity across backend, frontend, and future AI layers.

All documents, code, and events must use these canonical terms.

---

## Identifier Format (MVP)

All entity identifiers are opaque strings.

Examples:
- productId: "prod_001"
- locationId: "loc_001"

No UUID enforcement is applied in Phase 1.

Future phases may standardize identifiers (e.g., UUID), but clients must treat IDs as opaque values.

---

## Canonical Terms

### Stock Item

A record representing a quantity of product in a specific location.

### Available Quantity

Stock that is operationally usable for allocation or movement.

### Blocked Quantity

Stock that exists physically but cannot be used operationally.

### Inventory Incident

An anomaly affecting stock, storage, handling, or execution.

### Inventory Movement

A traceable transition of stock from one operational state or location to another.

### Warehouse Location

A physical address inside the warehouse structure.

### Product

A master catalog entry representing a sellable or storable item identified by SKU.

### Warehouse

A physical facility containing zones, aisles, racks, levels, and bins.

### Audit Log

A chronological record of who performed what action, when, and on which entity.

---

## Incident Types

- damage
- shortage
- overage
- expiration
- misplacement
- broken_packaging
- nonconforming_product
- picking_blocker
- lot_error

---

## Incident Status Values

- open
- in_review
- resolved
- closed

### Allowed Transitions

| From | To |
| :--- | :--- |
| `open` | `in_review`, `closed` |
| `in_review` | `resolved`, `closed` |
| `resolved` | `closed` |
| `closed` | _(terminal state)_ |

---

## Incident Severity Values

- low
- medium
- high

---

## Movement Types

- receipt
- putaway
- relocation
- adjustment
- picking
- return_internal

---

## Inventory Status Values

- available
- blocked
- in_transit
- quarantine

---

## Adjustment Reason Values

- manual_adjustment
- cycle_count
- incident_damage
- incident_shortage
- quality_hold
- correction

> [!IMPORTANT]
> The `reason` field on movements of type `adjustment` is **not free-text**. It must be one of the values above. This is enforced at the API layer via the `AdjustmentReasonSchema` in `packages/shared-schemas/src/movements.ts`. The backend must validate reason values using the same controlled vocabulary.

---

## Actor Types

- human
- system
- agent

---

## Roles

- operator
- supervisor
- admin
- system
- agent

---

## Event Types

### Inventory Events

- inventory.stock.adjusted
- inventory.stock.relocated
- inventory.stock.received
- inventory.stock.picked
- inventory.stock.putaway
- inventory.stock.returned

### Incident Events

- incident.reported
- incident.status.updated

### Movement Events

- movement.created

### Location Events

- location.blocked
- location.unblocked

### Product Events

- product.created
