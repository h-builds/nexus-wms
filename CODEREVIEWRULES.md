## Rule 10 — Frontend Must Follow Domain Structure

Frontend code must be organized by business domain.

Prefer:

- `domains/inventory`
- `domains/incidents`
- `domains/monitoring`
- `domains/layout`
- `domains/simulation`

Avoid:

- large generic folders with mixed ownership
- utility sprawl
- cross-domain shared state without explicit need

---

## Rule 21 — Comments Must Add Real Value

Comments are allowed only when they explain something non-obvious.

Allowed comments:

- business rules
- domain constraints
- safety conditions
- audit implications
- edge cases
- architectural tradeoffs

Forbidden comments:

- comments that restate the code
- comments that describe obvious assignments
- comments that narrate simple control flow
- placeholder comments with no operational value

Examples of forbidden comments:

- "this variable stores the product id"
- "this function returns the result"
- "loop through movements"
- "set value to true"
- "check if it exists"

Rule:
If the code is self-explanatory, do not add comments.

---

## Rule 22 — Naming Must Be Explicit and Domain-Driven

Names must reflect business meaning.

Prefer:

- stockItem
- inventoryAdjustment
- warehouseLocation
- incidentStatus
- movementReference
- auditEntry

Avoid generic names like:

- data
- item
- value
- temp
- result
- info
- obj
- thing

Function names must describe intent:

- registerMovement
- reportIncident
- resolveIncident
- adjustInventory
- blockLocation

Avoid vague names like:

- handleData
- processItem
- updateThing
- doAction

---

## Rule 23 — Functions Must Stay Focused

Functions should be small and single-purpose.

Prefer:

- one clear responsibility
- explicit inputs
- explicit outputs
- early returns
- simple control flow

Avoid:

- large multi-purpose functions
- deeply nested conditions
- mixed validation + business logic + formatting in one place
- hidden mutations

If logic becomes hard to explain briefly, split it.

---

## Rule 26 — Type Safety Is Mandatory

Use explicit types whenever possible.

Required:

- typed DTOs
- typed payloads
- shared contracts for cross-surface data
- validated external input

Avoid:

- implicit any
- loose object shapes
- mixed payload conventions
- untyped event payloads

External input must never be trusted without validation.

---

## Rule 28 — Error Handling Must Be Meaningful

Do not fail silently.

Required:

- explicit validation failures
- meaningful domain errors
- safe user-facing messages
- traceable backend failures

Avoid:

- swallowed exceptions
- empty catch blocks
- generic "something went wrong" everywhere
- leaking stack traces to clients

---

## Rule 29 — Frontend Code Must Stay Clean

Frontend code must reflect domain structure and avoid business drift.

Prefer:

- domain-based folders
- small composables
- typed API responses
- presentational components separated from data-fetching logic

Avoid:

- giant components
- duplicated transformation logic across pages
- mixing mock logic with real contracts
- frontend business rules that should live in backend

---

## Rule 31 — Vue Code Must Stay Intentional

Vue code must prefer:

- clear component names
- small, domain-aligned composables
- explicit props
- explicit emits
- low coupling between domains

Avoid:

- shared global state unless clearly justified
- magic watchers
- hidden side effects inside components
- large files that mix fetching, transformation, UI, and domain decisions

---

## Rule 38 — Vue and TypeScript Best Practices (2026)

Vue code must reflect domain ownership and TypeScript discipline.

Required:

- domain-based folder structure
- TypeScript-first implementation
- explicit props and emits
- typed API contracts
- composables with clear purpose
- separation between view composition and data-fetching concerns

Prefer:

- small components
- presentational components with minimal side effects
- composables for reusable UI behavior
- stores only when state is truly shared
- explicit loading, error, and empty states

Avoid:

- giant single-file components
- business rules hidden in watchers
- implicit prop behavior
- mixed responsibilities inside one component
- duplicated mapping logic across views

Do not:

- treat the frontend as source of business truth
- duplicate backend domain rules in UI unless strictly needed for UX
- hide complex behavior inside computed chains that reduce readability

---

## Rule 39 — TypeScript Strictness Is Non-Negotiable

All TypeScript code must assume strict mode discipline.

Required:

- no implicit any
- explicit typing for external data
- discriminated unions or enums for stateful domain values
- typed event payloads
- typed API responses
- narrow unknown values before use

Avoid:

- broad Record<string, any> unless unavoidable and isolated
- leaking unknown backend data shapes into components
- casual casting to bypass compiler feedback
- mixed payload conventions across modules

Rule:
TypeScript must be used to reduce ambiguity, not to decorate ambiguous code.
