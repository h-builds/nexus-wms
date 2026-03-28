# NexusWMS Prompt Library

## Purpose

This file defines reusable prompt templates for interacting with AI assistants in NexusWMS.

The goal is:

- consistency
- alignment with governance rules
- reduction of ambiguity
- faster and safer implementation

Prompts must:

- respect domain boundaries
- align with API contracts
- follow event catalog definitions
- comply with data guardrails
- produce auditable outputs

---

## Prompt Structure Standard

All prompts should include:

1. Objective
2. Domain Context
3. Constraints
4. Expected Output
5. References (docs)

---

## 1. Endpoint Creation Prompt

### Template

Objective:
Create a backend endpoint for [ACTION].

Domain Context:

- Domain: [Inventory / Movements / Incidents / Products / Locations]
- Operation type: [Command / Query]
- Related entities: [...]

Constraints:

- must follow API_SPEC
- must respect domain ownership
- must not place business logic in controllers
- must be traceable
- must be event-ready (if command)
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- route definition
- controller
- application service
- request validation
- response structure

References:

- docs/API_SPEC.md
- docs/DOMAIN_MODEL.md
- docs/EVENT_CATALOG.md (if command)

---

## 2. Domain Service Prompt

### Template

Objective:
Design a domain/application service for [USE CASE].

Domain Context:

- domain: [...]
- entities involved: [...]
- operation: [...]

Constraints:

- no controller logic
- must preserve domain boundaries
- must be explicit and testable
- must support auditability
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- service structure
- method signature
- core logic
- explanation of domain ownership

References:

- docs/DOMAIN_MODEL.md

---

## 3. Event Design Prompt

### Template

Objective:
Define the event for [ACTION].

Domain Context:

- triggering action: [...]
- domain: [...]
- source endpoint: [...]

Constraints:

- must follow EVENT_CATALOG naming
- must be past tense
- must be immutable
- payload must be minimal but sufficient
- no sensitive data
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- event name
- trigger condition
- payload definition
- reasoning for included fields

References:

- docs/EVENT_CATALOG.md
- docs/DATA_DICTIONARY.md

---

## 4. Refactor Prompt

### Template

Objective:
Refactor the following code to align with NexusWMS architecture.

Code:
[paste code]

Constraints:

- remove business logic from controllers
- enforce domain boundaries
- eliminate duplication
- improve traceability
- keep behavior unchanged
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- refactored code
- explanation of improvements
- identified rule violations

References:

- .ai/RULES.md
- docs/ARCHITECTURE.md

---

## 5. Validation Prompt

### Template

Objective:
Validate the following implementation against NexusWMS governance.

Input:
[paste code or description]

Constraints:

- use EVALS.md
- check domain correctness
- check architecture compliance
- check data safety
- check event integrity
- check traceability
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- evaluation per dimension
- severity classification
- recommended fixes

References:

- .ai/EVALS.md

---

## 6. Data Exposure Review Prompt

### Template

Objective:
Review data exposure risks in this payload/response/event.

Input:
[payload]

Constraints:

- apply DATA_GUARDRAILS.md
- minimize exposure
- detect sensitive data
- identify unnecessary fields
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- safe fields
- risky fields
- recommended changes

References:

- .ai/DATA_GUARDRAILS.md

---

## 7. Frontend Integration Prompt

### Template

Objective:
Integrate frontend view for [FEATURE].

Domain Context:

- domain: [...]
- API endpoint: [...]
- data needed: [...]

Constraints:

- must follow domain-based structure
- must not duplicate backend logic
- must keep UI state minimal
- must align with API contracts
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- component structure
- data fetching logic
- state handling
- rendering strategy

References:

- docs/API_SPEC.md
- docs/ARCHITECTURE.md

---

## 8. Event Flow Prompt

### Template

Objective:
Describe the full flow for [USE CASE].

Context:

- user action: [...]
- endpoint: [...]
- domain: [...]

Constraints:

- include command
- include domain logic
- include event emission
- include state changes
- include audit implications
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- step-by-step flow
- entities involved
- events emitted
- traceability explanation

References:

- docs/API_SPEC.md
- docs/EVENT_CATALOG.md
- docs/DOMAIN_MODEL.md

---

## 9. AI-Agent Task Prompt

### Template

Objective:
Define how an AI agent should assist with [TASK].

Context:

- domain: [...]
- inputs: [...]
- expected outputs: [...]

Constraints:

- must not violate RULES.md
- must respect DATA_GUARDRAILS.md
- must not invent data
- must be explainable
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- agent responsibility
- decision boundaries
- input/output structure
- risks and limitations

References:

- .ai/CONTEXT.md
- .ai/RULES.md
- .ai/DATA_GUARDRAILS.md

---

## 10. Anti-Pattern Detection Prompt

### Template

Objective:
Identify anti-patterns in the following implementation.

Input:
[paste code]

Constraints:

- detect domain violations
- detect hidden coupling
- detect controller misuse
- detect duplication
- detect non-auditable logic
- generated code must comply with .ai/RULES.md, including Laravel, Vue, TypeScript, modular monolith, and no-AI-slop rules

Expected Output:

- list of anti-patterns
- severity level
- suggested corrections

References:

- .ai/RULES.md
- .ai/EVALS.md

---

## Final Note

These prompts are not rigid scripts.

They are structured thinking tools to ensure:

- alignment
- clarity
- governance compliance
- production-grade outputs

AI should adapt them to context, but not ignore their intent.
