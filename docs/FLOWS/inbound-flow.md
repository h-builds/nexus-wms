# Inbound Flow

## Purpose

This flow defines how stock enters NexusWMS through inbound operations.

It covers:
- **Reception**: Initial entry and physical verification of goods.
- **Validation**: Strict verification of product, SKU, and location data.
- **Assignment**: Placing stock into validated warehouse locations.
- **Impact**: Real-time inventory creation or increase.
- **Audit**: Full traceability of the inbound movement.

This flow is foundational because it creates the initial stock state used by the rest of the system.

---

## Scope

### Domain(s) Involved
- **Product**: Validates SKU and master data.
- **Locations**: Validates storage suitability.
- **Inventory**: Manages stock levels.
- **Movements**: Records physical transitions.
- **Audit**: Tracks actor and system actions.
- **Events**: Communicates state changes.

### Actors
- **Warehouse Operator**: Performs the physical reception and system entry.
- **Supervisor**: Handles exceptions and blocked-location overrides.
- **System**: Automates validations and event emissions.

---

## High-Level Flow

1. **Start**: Inbound reception process initiated.
2. **Validate**: Product and location eligibility are verified.
3. **Confirm**: Received quantity and lot/serial numbers are recorded.
4. **Mutate**: Inventory records are created or increased.
5. **Register**: A physical movement record is generated.
6. **Emit**: Domain events are broadcasted to the system.
7. **Audit**: All actions are logged for compliance.

---

## Detailed Flow

### 1. Reception Start

**Source:**
- Warehouse receiving station.
- Future mobile flow for floor-based reception.
- future ERP/External integration source.

**Operation Type:** `Command`

**Purpose:** Begin the formal entry of stock into the warehouse orchestration layer.

---

### 2. Product Validation

The system must perform the following checks:
- Does the product exist in the master catalog?
- Is the SKU valid and active?
- Does the product configuration allow reception in the current warehouse context?

**Rule:** If the product is invalid, the operation is rejected immediately. No inventory is created, and no events are emitted.

---

### 3. Location Validation

The system must perform the following checks:
- Does the target location exist?
- Does it belong to the correct warehouse zone?
- Is the location blocked for inbound?

**Future Consideration:**
- Location suitability for specific product types (e.g., hazardous, refrigerated) will require additional product and location attributes not yet defined in the domain model.

**Rule:** If the location is invalid or blocked, the operation is rejected. Operator correction or supervisor override is required.

---

### 4. Quantity Confirmation

The operator confirms the physical units received:
- **Quantity**: Must be numeric and greater than zero.
- **Attributes**: Lot number or serial number must be provided if required by product rules.

**Future Considerations:**
- Expected vs. Received quantity reconciliation.
- Over-receipt tolerance limits.
- Automatic splitting of damaged quantities.

---

### 5. Inventory Mutation

If all validations pass, the system updates the inventory state:
- **New Context**: Create a new `StockItem` if none exists for the product/location/lot combination.
- **Existing Context**: Increase the `quantityOnHand` for the matching `StockItem`.

**Inventory Impact:**
- `quantityOnHand` increases.
- `quantityAvailable` increases.
- `quantityBlocked` remains unchanged (unless specific quality rules trigger immediate quarantine).

> [!IMPORTANT]
> No negative or inconsistent stock states are allowed. Inbound operations must never bypass the movement registration layer.

---

### 6. Movement Registration

A physical movement record must be generated to explain the inventory increase.

**Movement Type:** `receipt`

**Required Data:**
- `productId`: Reference to the product.
- `toLocationId`: Target location.
- `quantity`: Total units received.
- `performedBy`: Actor ID.
- `performedAt`: High-precision timestamp.
- `reference`: External document ID (e.g., PO # or Delivery Note).
- `lotNumber`: If applicable.

---

### 7. Event Emission

After successful persistence, the following events are emitted:

**Event: `movement.created`**
```json
{
  "movementId": "mov_001",
  "productId": "prod_001",
  "type": "receipt",
  "quantity": 100,
  "toLocationId": "loc_001"
}
```

**Event: `inventory.stock.received`**
```json
{
  "productId": "prod_001",
  "locationId": "loc_001",
  "quantity": 100,
  "lotNumber": "LOT-2026-001"
}
```

> [!NOTE]
> Events are emitted only after the transaction is successfully committed and are treated as immutable facts.

---

### 8. Audit Trail

The system must record:
- **Who**: Operator or system ID.
- **What**: Detailed summary of the received load.
- **Where**: Specific warehouse and location IDs.
- **When**: Timestamp of completion.
- **Links**: Connections to movement IDs and event IDs.

**Audit is mandatory for:**
- Inbound registrations.
- Supervisor overrides.
- Exception handling for blocked locations.

---

## API Alignment

**Command Surface:** `POST /api/movements`

For the MVP, inbound operations are modeled as a movement of type `receipt`.

**Example Request:**
```json
{
  "productId": "prod_001",
  "toLocationId": "loc_001",
  "type": "receipt",
  "quantity": 100,
  "reference": "inbound_receipt_001"
}
```

> [!NOTE]
> `performedBy` is set automatically from the authenticated session.

---

## AI Execution Guardrails

### AI Assistance (Advisory Only)
- **Anomaly Detection**: Flagging unusual reception quantities compared to historical averages.
- **Normalization**: Standardizing notes or comments from the reception floor.
- **Discrepancy Detection**: Comparing physical input to future digital manifests (ASN).

### AI Restrictions
- **No Direct Mutation**: AI cannot create inventory without passing through the validated command flow.
- **No Validation Bypass**: AI cannot override product or location validation rules.
- **Rule Enforcement**: AI cannot bypass blocked-location status without supervisor escalation.

---

## Failure Scenarios

- **Invalid Product/SKU**: Reject operation; no mutation; log error.
- **Invalid/Blocked Location**: Reject operation; require corrective action.
- **Invalid Quantity**: Reject operation; block submission until corrected.
- **Persistence Failure**: Atomic rollback; no events emitted; log for system investigation.

---

## Observability & Metrics

**KPs to Track:**
- Inbound throughput (lines per hour).
- Product reception frequency.
- Rejection rate due to validation errors.
- Successful vs. Blocked location attempts.
- Ratio of new stock creation vs. existing stock increase.

---

## Future Extensions

- **ASN Reconciliation**: Matching physical arrivals against Advance Shipping Notices.
- **Quality Quarantine**: Automated blocking of stock based on quality rules.
- **Mobile Vision**: AI-assisted barcode and label scanning for faster throughput.
- **Supplier Scoring**: Tracking inbound accuracy and quality per vendor.

---

## Summary

This flow ensures that inbound stock entry is:
- **Validated**: Protected by strict business rules.
- **Traceable**: Logged at every step of the transaction.
- **Event-Driven**: Synchronized across the architectural layers.
- **Audit-Ready**: Fully compliant with enterprise tracking requirements.

---
