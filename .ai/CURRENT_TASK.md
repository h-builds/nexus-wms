# CURRENT TASK — Phase 4.1: Integration Backbone Definition

## Objective

Define and align the system architecture for full cross-module integration using a unified event-driven backbone.

## Context

Phase 3 delivered three independent but functional surfaces:

- Field-Agent Mobile (data capture)
- Vapor Monitor (real-time visualization)
- Orchestrator Twin (read-only tactical intelligence)

However, these modules are not yet fully integrated through a shared event pipeline.

## Problem

The system currently behaves as partially connected components instead of a coordinated distributed system.

There is no:

- unified event flow across all modules
- cross-surface correlation model
- persistent decision trace
- defined integration architecture

## Scope of this task

This phase is strictly architectural and documentation-focused.

DO NOT implement:

- UI changes
- AI decision engines
- new endpoints

## Required Outcomes

### 1. Integration Backbone Definition

- Define how events flow from API → Event Bus → Consumers
- Establish canonical event lifecycle

### 2. Correlation Model

- Standardize usage of:
  - correlationId
  - causationId
- Ensure traceability across all modules

### 3. Cross-Surface Integration Model

Define how each module participates:

- Field-Agent → emits domain actions
- API → persists + emits events
- Vapor Monitor → subscribes for visibility
- Orchestrator → consumes for analysis

### 4. Decision Logging (Conceptual Only)

Define:

- what a decision log is
- when it should be created
- relationship with events

(NO persistence yet)

## Files to update

- docs/ARCHITECTURE.md
- docs/EVENT_CATALOG.md (only if needed)
- docs/FLOWS/integration-flow.md (create if missing)
- .ai/AGENTS.md (extend for decision trace concept)

## Success Criteria

- Clear event-driven architecture defined
- Cross-module integration explicitly documented
- Correlation model standardized
- No implementation performed

## Constraints

- Follow existing API_SPEC and EVENT_CATALOG conventions
- Do not introduce breaking changes
- Maintain suggestion-first agent model (SECURITY_MODEL)
