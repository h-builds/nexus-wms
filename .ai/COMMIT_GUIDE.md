# NexusWMS Commit Guide

## Purpose

This guide defines commit standards for human and AI-assisted changes.

Commits must be:

- atomic
- readable
- reviewable
- scoped
- reversible

---

## Commit Format

Use:

type(scope): short description

Examples:

- feat(inventory): add stock adjustment command
- fix(incidents): validate status transition
- refactor(api): extract movement service
- docs(events): add full event example
- test(locations): cover blocked location rule
- chore(repo): align workspace scripts

---

## Allowed Types

- feat
- fix
- refactor
- docs
- test
- chore

---

## Scope Rules

Scope should describe the affected domain or layer.

Good scopes:

- inventory
- incidents
- movements
- locations
- api
- events
- security
- ai
- docs
- vapor-monitor
- orchestrator-twin

Avoid vague scopes:

- stuff
- update
- misc
- changes

---

## Description Rules

Description must:

- be short
- be explicit
- use present tense
- stay under 72 characters when possible

Good:

- feat(inventory): add stock lookup endpoint
- fix(events): include actorId in movement event

Bad:

- fix: update things
- refactor(code): improve logic
- chore: changes

---

## Atomic Commit Rule

Each commit should represent one logical change.

Allowed:

- one endpoint
- one bug fix
- one refactor
- one documentation update group

Avoid:

- mixing feature + refactor + docs without reason
- giant dump commits
- formatting-only noise mixed with behavior changes

---

## Commit Sequence Guidance

Preferred order when possible:

1. docs/spec changes
2. domain or contract changes
3. implementation
4. tests
5. integration
6. final docs sync

---

## AI-Assisted Commit Rule

If AI helped generate the change:

- review before commit
- remove meaningless comments
- remove fake placeholder logic
- ensure commit message reflects real behavior

Do not commit raw AI output without cleanup.

---

## Examples

### Good Commit History

- docs(api): define movement endpoint contract
- feat(movements): add movement creation service
- test(movements): cover relocation validation
- docs(events): add movement.created payload

### Bad Commit History

- update files
- fix issues
- more changes
- final fix
