# ADR-002: Modular Monolith Backend

## Status

Accepted

## Decision

The backend uses a modular monolith in Laravel instead of microservices.

## Why

The project needs clear domain boundaries without premature distributed complexity.

## Consequences

- lower operational complexity
- faster MVP execution
- easier future extraction if needed
