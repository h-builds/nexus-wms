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
  ]
}
```

Note:
Pagination is expected in future iterations but is intentionally omitted from the initial MVP.

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
      "unitOfMeasure": "unit"
    }
  ]
}
```

Note:
Pagination is expected in future iterations but is intentionally omitted from the initial MVP.

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
    "unitOfMeasure": "unit"
  }
}
```

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
  ]
}
```

Note:
Pagination is expected in future iterations but is intentionally omitted from the initial MVP.

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
      "description": "Outer package is broken",
      "status": "open",
      "reportedBy": "user_001",
      "createdAt": "2026-03-27T12:30:00Z"
    }
  ]
}
```

Note:
Pagination is expected in future iterations but is intentionally omitted from the initial MVP.

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
    "description": "Outer package is broken",
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
  "description": "Outer package is broken",
  "reportedBy": "user_001"
}
```

Response:

```json
{
  "data": {
    "id": "inc_001",
    "productId": "prod_001",
    "locationId": "loc_001",
    "type": "damage",
    "description": "Outer package is broken",
    "status": "open",
    "reportedBy": "user_001",
    "createdAt": "2026-03-27T12:30:00Z"
  }
}
```

Status codes:

- `201 Created`
- `422 Unprocessable Entity`

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
  ]
}
```

Note:
Pagination is expected in future iterations but is intentionally omitted from the initial MVP.

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
  "performedBy": "user_002",
  "reference": "manual_relocation"
}
```

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

Validation rules:

- quantity must be a positive integer
- productId must exist
- fromLocationId and toLocationId must exist
- fromLocationId cannot equal toLocationId

Business constraints:

- cannot move more than available quantity
- cannot move stock from a blocked location
- cannot move stock to a blocked location

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
