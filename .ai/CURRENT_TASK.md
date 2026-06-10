# CURRENT TASK — Phase 4.3.4: System Validation

## Objective

Validate that the full event-driven pipeline behaves deterministically and consistently across all surfaces.

## Context

Phase 4.3.3 completed:

- ingestion layer
- interpretation layer
- UI binding

The system is now fully connected end-to-end.

## Problem

Even with correct architecture, subtle inconsistencies can exist:

- duplicate processing
- state drift
- ordering issues
- contract mismatches

## Scope

### In Scope

- validate event → state → UI flow
- validate baseline + stream consistency
- validate cross-surface synchronization
- validate deterministic replay behavior

### Out of Scope

- new features
- UI redesign
- performance optimization
- AI/decision layers

## Requirements

### 1. Event Replay Consistency

- same sequence of events → identical interpreted state
- no divergence between Vapor and Twin

### 2. Duplicate Protection

- re-sending same event must NOT mutate state twice

### 3. Baseline + Stream Integrity

- initial load shows correct state
- new events update state incrementally

### 4. Cross-Surface Sync

- both apps must reflect identical system state at all times

### 5. Contract Validation

- all interpreted fields must come from canonical payloads
- no inferred data

## Validation

Test scenarios:

1. reload app → verify baseline renders correctly
2. trigger movement → verify both apps update
3. trigger incident → verify correct bin + KPI update
4. replay same event → verify no duplication
5. simulate event burst → verify ordering integrity

## Success Criteria

- system behaves deterministically
- no UI drift between apps
- no duplicated processing
- no contract violations
