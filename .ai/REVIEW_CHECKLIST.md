# NexusWMS Review Checklist

## Purpose

Use this checklist for reviewing human or AI-assisted changes.

A change is not accepted just because it works.
It must also preserve governance, clarity, and system integrity.

---

## 1. Domain Ownership

- [ ] Is the owning domain clear?
- [ ] Is logic placed in the correct domain?
- [ ] Are responsibilities mixed incorrectly?
- [ ] Is any new field or service ownerless?

---

## 2. Architectural Compliance

- [ ] Are controllers thin?
- [ ] Is business logic outside controllers?
- [ ] Does the change preserve modular boundaries?
- [ ] Does it avoid cross-domain shortcuts?

---

## 3. Data Safety

- [ ] Is data exposure minimal?
- [ ] Are logs safe?
- [ ] Are event payloads controlled?
- [ ] Is sensitive data avoided?
- [ ] Is prompt injection risk considered for untrusted content?

---

## 4. API Contract Alignment

- [ ] Does the change follow `docs/API_SPEC.md`?
- [ ] Are request/response shapes consistent?
- [ ] Are status codes intentional?
- [ ] Was documentation updated if needed?

---

## 5. Event Integrity

- [ ] Are events past-tense and meaningful?
- [ ] Are events emitted after successful operations?
- [ ] Is payload minimal but sufficient?
- [ ] Are events immutable facts?

---

## 6. Traceability

- [ ] Is actor attribution present when relevant?
- [ ] Can the operation be audited later?
- [ ] Are state changes explainable?
- [ ] Is hidden mutation avoided?

---

## 7. Code Quality

- [ ] Are names explicit and domain-driven?
- [ ] Are functions focused?
- [ ] Are abstractions justified?
- [ ] Are comments meaningful?
- [ ] Is there any AI slop?

---

## 8. Type Safety

- [ ] Are external inputs validated?
- [ ] Are types explicit enough?
- [ ] Is implicit any avoided?
- [ ] Are payloads/contracts typed?

---

## 9. Frontend Integrity

- [ ] Does frontend respect domain structure?
- [ ] Is backend logic duplicated in UI?
- [ ] Are components focused?
- [ ] Is state handling minimal and intentional?

---

## 10. Documentation Alignment

- [ ] Was documentation updated if behavior changed?
- [ ] Does implementation still match domain model?
- [ ] Does implementation still match event catalog?
- [ ] Does implementation still match AI governance assumptions?

---

## 11. Reviewability

- [ ] Is the diff focused?
- [ ] Is the PR understandable quickly?
- [ ] Are unrelated changes avoided?
- [ ] Is the change easy to reason about?

---

## 12. Final Decision

### Accept if:

- domain is correct
- architecture is preserved
- data is safe
- events are correct
- traceability is preserved
- code is maintainable

### Reject if:

- hidden mutations exist
- boundaries are broken
- data exposure is unsafe
- events are wrong or unclear
- docs drift from implementation
- code is noisy, vague, or misleading
