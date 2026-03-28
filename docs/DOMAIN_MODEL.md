```md
# NexusWMS Domain Model

## Domain Goal

NexusWMS models warehouse execution, monitoring, and orchestration.

The initial focus is not full enterprise WMS coverage.
The initial focus is the minimum viable operational core needed for:

- stock visibility
- movement traceability
- incident capture
- physical location awareness
- auditability

---

## Core Domains

### 1. Inventory

Owns the current stock truth.

Responsibilities:

- quantity on hand
- available quantity
- blocked quantity
- inventory condition
- lot and serial context
- received date
- expiration date

Key concepts:

- StockItem
- InventoryStatus
- InventoryBalance
- InventoryCondition

---

### 2. Incidents

Owns anomaly reporting and lifecycle.

Responsibilities:

- incident registration
- incident type classification
- open / review / resolved / closed state
- evidence references
- ownership and escalation
- corrective action traceability

Key concepts:

- InventoryIncident
- IncidentType
- IncidentStatus
- IncidentSeverity

---

### 3. Movements

Owns operational stock transitions.

Responsibilities:

- receipt
- putaway
- relocation
- adjustment
- picking
- internal return

Key concepts:

- InventoryMovement
- MovementType
- MovementReference
- MovementActor

---

### 4. Locations

Owns the physical warehouse structure.

Responsibilities:

- warehouse identity
- zone
- aisle
- rack
- level
- bin
- location status

Key concepts:

- Warehouse
- WarehouseLocation
- LocationCapacity
- LocationBlockStatus

---

### 5. Identity

Owns user identity and operational actor references.

Responsibilities:

- authenticated user
- operator identity
- supervisor identity
- action attribution

Key concepts:

- User
- Role
- Permission
- ActorReference

---

### 6. Audit

Owns traceability of meaningful operational changes.

Responsibilities:

- who created
- who changed
- who approved
- who closed
- when it happened
- what changed

Key concepts:

- AuditLog
- AuditAction
- AuditActor
- AuditTarget

---

### 7. Product

Owns product identity and master data.

Responsibilities:

- SKU definition
- product name
- product category
- unit of measure
- product attributes

Key concepts:

- Product
- SKU
- ProductCategory

---

## Initial Entity Candidates

### StockItem

Represents a product-position stock record.

Initial attributes:

- id
- productId
- locationId
- quantityOnHand
- quantityAvailable
- quantityBlocked
- lotNumber
- serialNumber
- receivedAt
- expiresAt
- status

### InventoryIncident

Represents a warehouse anomaly.

Initial attributes:

- id
- productId
- locationId
- type
- description
- status
- reportedBy
- createdAt

### InventoryMovement

Represents a stock transition.

Initial attributes:

- id
- productId
- fromLocationId
- toLocationId
- type
- quantity
- performedBy
- performedAt
- reference

### Warehouse

Represents a physical warehouse facility.

Attributes:

- id
- name
- address
- timezone
- type

### WarehouseLocation

Represents a physical storage position.

Initial attributes:

- id
- warehouseId
- zone
- aisle
- rack
- level
- bin
- label
- isBlocked

---

## Planned Relationships

### Inventory

- belongs to one product
- belongs to one location
- may reference one lot
- may reference one serial number

### Incident

- references one product
- references one location
- is reported by one actor
- may produce one or more corrective actions

### Movement

- references one product
- may move between two locations
- is executed by one actor

### Location

- belongs to one warehouse
- may contain multiple stock records

---

## Initial Business Rules

1. Inventory is the source of stock truth.
2. Movements change inventory state.
3. Incidents do not directly change stock truth unless a corrective movement or adjustment is executed.
4. Every meaningful movement must be attributable to an actor.
5. Every incident must be attributable to an actor.
6. A blocked location should not be treated as freely usable.
7. A blocked quantity should not be counted as available quantity.

---

## Out of Scope for Initial MVP

Not modeled in detail yet:

- procurement lifecycle
- supplier evaluation
- billing integration
- robot fleet control
- transport planning
- wave planning
- labor standards engine
- advanced FEFO/FIFO automation
- advanced replenishment optimization

These may be added after the operational core is stable.
```

## State Transitions

Inventory state is modified through movements.

Examples:

- receipt → increases quantityOnHand
- putaway → changes location
- picking → decreases quantityAvailable
- adjustment → modifies quantity directly

Incidents do not change inventory unless a corrective movement is executed.

This ensures:

- traceability
- auditability
- controlled mutations
