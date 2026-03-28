# NexusWMS Data Dictionary

## Purpose

This file defines the first controlled vocabulary for the project.
It exists to reduce ambiguity across backend, frontend, and future AI layers.

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
