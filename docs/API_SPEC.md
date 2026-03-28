Version:
v1

# NexusWMS API Specification

## Purpose

This document defines the first MVP API surface for NexusWMS.

It is intentionally limited to the operational core required for:

- inventory lookup
- incident registration
- movement registration
- location lookup
- health verification

This is not the final enterprise API.
It is the first controlled contract for implementation.

---

## API Style

- REST-first
- JSON request/response
- resource-oriented naming
- explicit command endpoints for operational actions
- future realtime integration will be handled separately

Base path:

```text
/api
```

---

## API Semantics

This API distinguishes between read operations and state-changing operations.

### Queries

Read-only endpoints used for rendering views, dashboards, and lookup operations.

Examples:

- GET /api/health
- GET /api/inventory
- GET /api/products
- GET /api/locations
- GET /api/incidents
- GET /api/movements

### Commands

State-changing endpoints used to register operational actions.

Examples:

- POST /api/incidents
- PATCH /api/incidents/{id}/status
- POST /api/movements

All command endpoints must generate audit logs.

---

## Response Conventions

### Success Response

```json
{
  "data": {}
}
```

### Error Response

```json
{
  "error": {
    "code": "string_code",
    "message": "Human readable message"
  }
}
```

### Validation Error Response (422)

```json
{
  "error": {
    "code": "validation_failed",
    "message": "Validation failed",
    "details": [
      {
        "field": "quantity",
        "message": "Quantity must be a positive integer"
      }
    ]
  }
}
```

---

## Actor Identity

> [!IMPORTANT]
> Actor identity (`reportedBy`, `performedBy`) is **always extracted from the authenticated session**.
> The API never accepts actor self-identification in request payloads.
> All examples in this document show these fields in responses only. They are server-set, not client-provided.

---

## Pagination

All list endpoints **must** support pagination from the initial implementation.

Query parameters:

- `page` (integer, default: 1)
- `per_page` (integer, default: 50, max: 100)

Paginated response envelope:

```json
{
  "data": [],
  "meta": {
    "currentPage": 1,
    "perPage": 50,
    "totalItems": 1234,
    "totalPages": 25
  }
}
```

> [!CAUTION]
> Unpaginated list endpoints will fail on real warehouse data volumes (50,000+ stock records). Pagination is not optional.

---

## Concurrency Control

State-changing operations on shared resources must handle concurrent access.

### Optimistic Locking

Entities that support concurrent modification (currently: `StockItem`) include a `version` field.

For movement operations that modify inventory:

1. Read the current `StockItem` with its `version`.
2. Apply business rules and quantity checks.
3. Issue `UPDATE ... SET version = version + 1 WHERE id = :id AND version = :expected_version`.
4. If zero rows affected → return `409 Conflict` with error:

```json
{
  "error": {
    "code": "conflict",
    "message": "Resource was modified by another operation. Please retry."
  }
}
```

### Database Constraints

The following CHECK constraints must exist at the database level:

- `quantityAvailable >= 0`
- `quantityBlocked >= 0`
- `quantityOnHand = quantityAvailable + quantityBlocked`

These are last-resort safety nets. Application logic must prevent violations before reaching the database.

---

## Idempotency

All `POST` command endpoints accept an optional `Idempotency-Key` HTTP header.

```text
Idempotency-Key: <client-generated-uuid>
```

Behavior:

- If the key is new, the operation executes normally.
- If the key was already processed successfully, the original response is returned without re-executing.
- If the key was already processed and failed, the operation may be retried.
- Keys expire after 24 hours.

This prevents duplicate movements and incidents from network retries, especially from offline-first mobile clients.

Applies to:

- `POST /api/movements`
- `POST /api/incidents`
- `POST /api/products`
- `POST /api/locations`

---

## Health

### GET /api/health

Purpose:

- verify backend availability
- allow frontend connectivity checks

Response:

```json
{
  "data": {
    "status": "ok"
  }
}
```

Status codes:

- `200 OK`

---

## Inventory

### GET /api/inventory

Purpose:

- list stock items
- allow future filtering by product, status, or location

Query params:

- `productId` (optional)
- `locationId` (optional)
- `status` (optional)

Response:

```json
{
  "data": [
    {
      "id": "stock_001",
      "productId": "prod_001",
      "locationId": "loc_001",
      "quantityOnHand": 100,
      "quantityAvailable": 92,
      "quantityBlocked": 8,
      "lotNumber": "LOT-2026-001",
      "serialNumber": null,
      "receivedAt": "2026-03-27T10:00:00Z",
      "expiresAt": null,
      "status": "available"
    }
  ],
  "meta": {
    "currentPage": 1,
    "perPage": 50,
    "totalItems": 1,
    "totalPages": 1
  }
}
```

### GET /api/inventory/{id}

Purpose:

- return one stock item by id

Response:

```json
{
  "data": {
    "id": "stock_001",
    "productId": "prod_001",
    "locationId": "loc_001",
    "quantityOnHand": 100,
    "quantityAvailable": 92,
    "quantityBlocked": 8,
    "lotNumber": "LOT-2026-001",
    "serialNumber": null,
    "receivedAt": "2026-03-27T10:00:00Z",
    "expiresAt": null,
    "status": "available"
  }
}
```

---

## Products

### GET /api/products

Purpose:

- list products
- support product lookup and later semantic/product search

Query params:

- `sku` (optional)
- `q` (optional)

Response:

```json
{
  "data": [
    {
      "id": "prod_001",
      "sku": "TV-001",
      "name": "Televisor Samsung 55",
      "category": "electronics",
      "unitOfMeasure": "unit",
      "attributes": {}
    }
  ],
  "meta": {
    "currentPage": 1,
    "perPage": 50,
    "totalItems": 1,
    "totalPages": 1
  }
}
```

### GET /api/products/{id}

Purpose:

- return one product by id

Response:

```json
{
  "data": {
    "id": "prod_001",
    "sku": "TV-001",
    "name": "Televisor Samsung 55",
    "category": "electronics",
    "unitOfMeasure": "unit",
    "attributes": {}
  }
}

Status codes:

- `200 OK`
- `404 Not Found`
```

### POST /api/products

Purpose:

- register a new product in the master catalog

Authorization:

- Admin only

Request Body:

```json
{
  "sku": "TV-001",
  "name": "Televisor Samsung 55",
  "category": "electronics",
  "unitOfMeasure": "unit",
  "attributes": {}
}
```

Response:

```json
{
  "data": {
    "id": "prod_001",
    "sku": "TV-001",
    "name": "Televisor Samsung 55",
    "category": "electronics",
    "unitOfMeasure": "unit",
    "attributes": {}
  }
}
```

Status codes:

- `201 Created`
- `409 Conflict` (duplicate sku)
- `422 Unprocessable Entity`

---

## Locations

### GET /api/locations

Purpose:

- list physical warehouse locations

Query params:

- `warehouseId` (optional)
- `zone` (optional)
- `aisle` (optional)
- `rack` (optional)
- `bin` (optional)

Response:

```json
{
  "data": [
    {
      "id": "loc_001",
      "warehouseId": "wh_001",
      "zone": "A",
      "aisle": "01",
      "rack": "R1",
      "level": "L2",
      "bin": "B3",
      "label": "A-01-R1-L2-B3",
      "isBlocked": false
    }
  ],
  "meta": {
    "currentPage": 1,
    "perPage": 50,
    "totalItems": 1,
    "totalPages": 1
  }
}
```

### GET /api/locations/{id}

Purpose:

- return one location by id

Response:

```json
{
  "data": {
    "id": "loc_001",
    "warehouseId": "wh_001",
    "zone": "A",
    "aisle": "01",
    "rack": "R1",
    "level": "L2",
    "bin": "B3",
    "label": "A-01-R1-L2-B3",
    "isBlocked": false
  }
}
```

### POST /api/locations

Purpose:

- register a new warehouse location

Authorization:

- Admin only

Request Body:

```json
{
  "warehouseId": "wh_001",
  "zone": "A",
  "aisle": "01",
  "rack": "R1",
  "level": "L2",
  "bin": "B3",
  "label": "A-01-R1-L2-B3"
}
```

Response:

```json
{
  "data": {
    "id": "loc_001",
    "warehouseId": "wh_001",
    "zone": "A",
    "aisle": "01",
    "rack": "R1",
    "level": "L2",
    "bin": "B3",
    "label": "A-01-R1-L2-B3",
    "isBlocked": false
  }
}
```

Status codes:

- `201 Created`
- `422 Unprocessable Entity`

### PATCH /api/locations/{id}/status

Purpose:

- block or unblock a warehouse location

Authorization:

- Supervisor or Admin

Request Body:

```json
{
  "isBlocked": true,
  "reason": "maintenance"
}
```

Response:

```json
{
  "data": {
    "id": "loc_001",
    "isBlocked": true
  }
}
```

Status codes:

- `200 OK`
- `404 Not Found`
- `422 Unprocessable Entity`

---

## Incidents

### GET /api/incidents

Purpose:

- list registered incidents
- support operational monitoring

Query params:

- `status` (optional)
- `type` (optional)
- `locationId` (optional)
- `productId` (optional)

Response:

```json
{
  "data": [
    {
      "id": "inc_001",
      "productId": "prod_001",
      "locationId": "loc_001",
      "type": "damage",
      "severity": "medium",
      "description": "Outer package is broken",
      "quantityAffected": 5,
      "status": "open",
      "reportedBy": "user_001",
      "createdAt": "2026-03-27T12:30:00Z"
    }
  ],
  "meta": {
    "currentPage": 1,
    "perPage": 50,
    "totalItems": 1,
    "totalPages": 1
  }
}
```

### GET /api/incidents/{id}

Purpose:

- return one incident by id

Response:

```json
{
  "data": {
    "id": "inc_001",
    "productId": "prod_001",
    "locationId": "loc_001",
    "type": "damage",
    "severity": "medium",
    "description": "Outer package is broken",
    "quantityAffected": 5,
    "status": "open",
    "reportedBy": "user_001",
    "createdAt": "2026-03-27T12:30:00Z"
  }
}
```

### POST /api/incidents

Purpose:

- register a new warehouse incident

Request Body:

```json
{
  "productId": "prod_001",
  "locationId": "loc_001",
  "type": "damage",
  "severity": "medium",
  "description": "Outer package is broken",
  "quantityAffected": 5
}
```

> [!NOTE]
> `reportedBy` is set automatically from the authenticated session.

Response:

```json
{
  "data": {
    "id": "inc_001",
    "productId": "prod_001",
    "locationId": "loc_001",
    "type": "damage",
    "severity": "medium",
    "description": "Outer package is broken",
    "quantityAffected": 5,
    "status": "open",
    "reportedBy": "user_001",
    "createdAt": "2026-03-27T12:30:00Z"
  }
}
```

Status codes:

- `201 Created`
- `409 Conflict` (duplicate idempotency key)
- `422 Unprocessable Entity`

> [!NOTE]
> **Edge case: `quantityAffected` exceeds `quantityAvailable`**
>
> If the reported `quantityAffected` is greater than the current `quantityAvailable` at the specified location, the incident is **still persisted** (reporting an anomaly is always valid). However, the stock-blocking adjustment will block only up to `quantityAvailable` (partial blocking). The incident's `quantityAffected` reflects the reported value; the movement's `quantity` reflects the actual blocked amount. Both values are preserved for audit.

### PATCH /api/incidents/{id}/status

Purpose:

- update the operational status of an incident

Request Body:

```json
{
  "status": "resolved"
}
```

Response:

```json
{
  "data": {
    "id": "inc_001",
    "status": "resolved"
  }
}
```

Allowed values:

- `open`
- `in_review`
- `resolved`
- `closed`

Allowed transitions:

| From | To |
| :--- | :--- |
| `open` | `in_review`, `closed` |
| `in_review` | `resolved`, `closed` |
| `resolved` | `closed` |
| `closed` | _(terminal state)_ |

> [!NOTE]
> Reopening a closed incident is not supported in the MVP. If reversal is needed, a new incident must be created.

Status codes:

- `200 OK`
- `404 Not Found`
- `422 Unprocessable Entity`

### PATCH /api/incidents/{id}

Purpose:

- update incident metadata during investigation

Request Body:

```json
{
  "notes": "Investigated packaging line. Root cause identified."
}
```

**Mutable fields** (whitelist — only these fields may be updated):

- `notes` (string)
- `assignedTo` (string, operator ID)

**Immutable fields** (rejected if present in request body):

- `type`
- `severity`
- `description`
- `productId`
- `locationId`
- `quantityAffected`
- `reportedBy`
- `createdAt`

Response:

```json
{
  "data": {
    "id": "inc_001",
    "notes": "Investigated packaging line. Root cause identified.",
    "updatedAt": "2026-03-27T15:00:00Z"
  }
}
```

Constraints:

- Updates are append-only in the audit trail.
- If any immutable field is present in the request body, return `422` with field-level error.

Status codes:

- `200 OK`
- `404 Not Found`
- `422 Unprocessable Entity`

---

## Movements

### GET /api/movements

Purpose:

- list inventory movements
- support audit and monitoring

Query params:

- `productId` (optional)
- `type` (optional)
- `fromLocationId` (optional)
- `toLocationId` (optional)

Response:

```json
{
  "data": [
    {
      "id": "mov_001",
      "productId": "prod_001",
      "fromLocationId": "loc_001",
      "toLocationId": "loc_002",
      "type": "relocation",
      "quantity": 5,
      "performedBy": "user_002",
      "performedAt": "2026-03-27T14:00:00Z",
      "reference": "manual_relocation"
    }
  ],
  "meta": {
    "currentPage": 1,
    "perPage": 50,
    "totalItems": 1,
    "totalPages": 1
  }
}
```

### GET /api/movements/{id}

Purpose:

- return one movement by id

Response:

```json
{
  "data": {
    "id": "mov_001",
    "productId": "prod_001",
    "fromLocationId": "loc_001",
    "toLocationId": "loc_002",
    "type": "relocation",
    "quantity": 5,
    "performedBy": "user_002",
    "performedAt": "2026-03-27T14:00:00Z",
    "reference": "manual_relocation"
  }
}
```

### POST /api/movements

Purpose:

- register an inventory movement

Request Body:

```json
{
  "productId": "prod_001",
  "fromLocationId": "loc_001",
  "toLocationId": "loc_002",
  "type": "relocation",
  "quantity": 5,
  "reference": "manual_relocation",
  "lotNumber": "LOT-2026-001"
}
```

> [!NOTE]
> `performedBy` is set automatically from the authenticated session.

Response:

```json
{
  "data": {
    "id": "mov_001",
    "productId": "prod_001",
    "fromLocationId": "loc_001",
    "toLocationId": "loc_002",
    "type": "relocation",
    "quantity": 5,
    "performedBy": "user_002",
    "performedAt": "2026-03-27T14:00:00Z",
    "reference": "manual_relocation"
  }
}
```

Allowed values for `type`:

- `receipt`
- `putaway`
- `relocation`
- `adjustment`
- `picking`
- `return_internal`

Validation rules (by movement type):

| Rule | receipt | putaway | relocation | adjustment | picking | return_internal |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| `fromLocationId` required | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `toLocationId` required | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| `from ≠ to` enforced | n/a | ✅ | ✅ | n/a | n/a | ✅ |

General validation rules:

- `quantity` must be a positive integer
- `productId` must exist
- location IDs, when required, must reference existing locations

Business constraints:

- cannot move more than available quantity (except `receipt` and `return_internal` which increase stock)
- cannot move stock from a blocked location
- cannot move stock to a blocked location
- concurrent modifications return `409 Conflict` (optimistic locking on StockItem)

Status codes:

- `201 Created`
- `409 Conflict` (concurrent modification or duplicate idempotency key)
- `422 Unprocessable Entity`

---

## MVP Boundaries

This specification intentionally excludes:

- procurement endpoints
- supplier workflows
- passkeys authentication endpoints
- realtime websocket channels
- digital twin simulation endpoints
- semantic search endpoints
- AI inference endpoints

These will be added after the operational core is stable.

---
