# NexusWMS Context Loading Strategy

## Purpose

This file defines how AI agents should load context in NexusWMS.

The goal is to reduce:

- token waste
- hallucinations
- context overload
- hidden drift

Rule:
Agents must never load the full repository context by default.

---

## Loading Priority

### 1. Always Load

- `.ai/active/PLAN.md`

This file defines:

- current phase
- active scope
- deferred work
- implementation order

---

### 2. Load by Task

Load only the documents relevant to the current task.

Examples:

#### Incident-related work

- `docs/FLOWS/incident-flow.md`
- relevant section of `docs/DOMAIN_MODEL.md`
- relevant section of `docs/API_SPEC.md`
- relevant section of `docs/EVENT_CATALOG.md`

#### Inbound-related work

- `docs/FLOWS/inbound-flow.md`
- relevant section of `docs/DOMAIN_MODEL.md`
- relevant section of `docs/API_SPEC.md`
- relevant section of `docs/EVENT_CATALOG.md`

---

### 3. Load Governance Selectively

Load governance documents only when needed.

Examples:

- `.ai/RULES.md` → when implementation or behavior rules matter
- `.ai/DATA_GUARDRAILS.md` → when handling untrusted input or exposed data
- `.ai/EVALS.md` → when reviewing quality or validating AI output
- `docs/SECURITY_MODEL.md` → when authorization, agent permissions, or prompt injection risk matter

Do not load these files completely unless the task truly requires it.

---

## Anti-Pattern

Never do this by default:

- load all files in `.ai/`
- load all files in `docs/`
- load full RULES.md for simple tasks
- load unrelated flows

This reduces precision and increases token usage.

---

## Principle

Context precision is better than context volume.

Load the minimum necessary context for the current task.
