# Full-Stack E2E Validation Guide (Layer 3)

This document provides step-by-step instructions on how to manually validate the end-to-end event-driven architecture and cross-surface data synchronization in NexusWMS.

These tests prove that Phase 4 (Total Integration) is fully functional: the Transactional Outbox correctly emits events, the API handles actions properly, the WebSocket Reverb server broadcasts events reliably, and both frontend dashboards update seamlessly without state drift.

---

## 1. Prerequisites (Starting the Services)

To test the system manually, you need to run all 3 main services simultaneously.

Open **3 separate terminal windows/tabs** and execute the following:

### Terminal 1: Backend API & WebSocket Server

```bash
cd apps/api
# This starts the Laravel API, the Reverb WebSocket server, and the queue worker
composer dev
```

### Terminal 2: Vapor Monitor (Dashboard)

```bash
cd apps/vapor-monitor
pnpm dev
```

### Terminal 3: Orchestrator Twin (Digital Twin)

```bash
cd apps/orchestrator-twin
pnpm dev
```

Once all services are running, open two browser windows side-by-side:

- **Vapor Monitor:** http://localhost:5173 (or the port specified by Vite)
- **Orchestrator Twin:** http://localhost:5174 (or the port specified by Vite)

---

## 2. E2E Scenario 1: Cross-Surface Convergence on Relocation

**Goal:** Prove that a stock movement triggered by the API correctly reaches both frontend surfaces simultaneously and updates the state.

1. Keep both browser windows (Vapor Monitor and Orchestrator Twin) visible on your screen.
2. Open a 4th terminal to act as the "Field Agent Mobile" (or use an API client like Postman) to trigger a stock relocation movement via the API.
3. First, fetch a valid product and location ID (or rely on the seeded ones):
   ```bash
   # Assuming prod_001 exists and has stock in loc_001
   ```
4. Execute a relocation movement using `curl`:
   ```bash
   curl -X POST http://localhost:8000/api/movements \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "productId": "prod_001",
       "fromLocationId": "loc_001",
       "toLocationId": "loc_002",
       "type": "relocation",
       "quantity": 5,
       "reference": "manual_relocation_test"
     }'
   ```
5. **Observe the frontends:**
   - **Vapor Monitor:** The Movement Event Log should immediately show an `inventory.stock.relocated` event. The overall inventory KPI widgets should update.
   - **Orchestrator Twin:** The Spatial Layout should flash or update the occupancy colors/markers for both `loc_001` (decreased) and `loc_002` (increased). The Event Log debugger should show the exact same event.

---

## 3. E2E Scenario 2: Incident Registration Reaction

**Goal:** Prove that a newly reported incident correctly propagates and applies visual anomalies in the physical layout mapping.

1. Execute an incident creation command:
   ```bash
   curl -X POST http://localhost:8000/api/incidents \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "productId": "prod_001",
       "locationId": "loc_002",
       "type": "damage",
       "severity": "high",
       "description": "Forklift hit the rack",
       "quantityAffected": 2
     }'
   ```
2. **Observe the frontends:**
   - **Vapor Monitor:** The "Open Incidents" KPI counter should increment by 1. The Incident Feed should immediately push the new incident to the top of the list.
   - **Orchestrator Twin:** A high-severity incident indicator (usually a red warning icon or spatial anomaly overlay) should appear directly on the bin corresponding to `loc_002` in the digital twin map.

---

## 4. E2E Scenario 3: Decision Intelligence Layer Triggering

**Goal:** Prove that the autonomous `InventoryAnomalyAgent` successfully detects large adjustments, persists a `DecisionTrace`, and alerts the frontends.

1. The `InventoryAnomalyAgent` is configured to trigger a trace when a stock adjustment drops the stock by ≥ 30%.
2. Execute a large negative adjustment:
   ```bash
   curl -X POST http://localhost:8000/api/movements \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "productId": "prod_001",
       "fromLocationId": "loc_002",
       "type": "adjustment",
       "quantity": 100,
       "reference": "massive_drop_test",
       "reason": "manual_adjustment"
     }'
   ```
3. **Observe the frontends:**
   - **Vapor Monitor:** A new **Decision Trace Alert** (advisory) should appear in the Intelligence feed highlighting the massive stock drop and suggesting an action (e.g., "Initiate cycle count").
   - **Orchestrator Twin:** The Intelligence/Decision Panel should populate with the same trace. The system metrics will report +1 active advisory.

---

## 5. E2E Scenario 4: Human-in-the-Loop Advisory Resolution

**Goal:** Prove that actions taken on a decision trace (acknowledging or acting upon it) sync across all applications simultaneously.

1. Find the `traceId` of the advisory generated in Scenario 3. (You can grab this from the Network tab or the UI ID display).
2. Execute a status update on the trace:
   ```bash
   curl -X PATCH http://localhost:8000/api/intelligence/traces/<traceId>/status \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "status": "acknowledged"
     }'
   ```
3. **Observe the frontends:**
   - **Both apps:** The Decision Trace card should instantly change its state badge from `pending` (or `advisory`) to `acknowledged`. The total count of "unacknowledged" critical alerts should drop.

---

## What to look out for during testing:

- **State Drift:** If Vapor Monitor shows 45 items in `loc_001` and Orchestrator Twin shows 50, drift has occurred. They must always show the identical final state.
- **Duplicate Events:** Ensure that triggering one `curl` command only renders _one_ event row in the frontends. The deduplication layer should drop duplicate events if they were sent twice by the WebSocket server.
- **Console Errors:** Keep an eye on the browser Developer Tools (F12) console. There should be no JavaScript errors during the live event injection.
