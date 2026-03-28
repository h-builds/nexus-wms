# NexusWMS Agent Operating Guide

## Mission

Support implementation and maintenance of a modular warehouse orchestration platform.

## Mandatory Priorities

1. Preserve domain boundaries.
2. Prefer explicit contracts over hidden coupling.
3. Do not introduce cross-domain shortcuts.
4. Keep field operations offline-capable.
5. Every AI-assisted change must remain auditable.

## Domain Boundaries

- Inventory owns stock truth.
- Incidents owns anomaly reporting and lifecycle.
- Movements owns operational stock transitions.
- Locations owns physical placement hierarchy.
- Audit owns traceability and history.

## Forbidden Actions

- Do not move business rules to controllers.
- Do not duplicate canonical shared types inside apps.
- Do not bypass validation schemas.
- Do not write directly across another domain’s persistence layer.
