# Nexus-WMS Optimization Protocol (Vue + Laravel)

# Priority: CRITICAL (Minimize KV Cache usage to avoid 7-day lockout)

## 1. Directory Exclusion (The "No-Fly" Zone)

To prevent redundant reads and token waste, DO NOT analyze or read:

- **Laravel Bloat:** `vendor/`, `storage/`, `bootstrap/cache/`, `public/storage/`.
- **Frontend Bloat:** `node_modules/`, `dist/`, `public/build/` (Vite manifests).
- **Global:** `pnpm-lock.yaml`, `.git/`, `.phpunit.cache/`.

## 2. Context Strategy

- **Laravel Context:** When working on PHP/Laravel logic, focus ONLY on `app/`, `routes/`, and `database/`. Ignore `resources/js/` unless explicitly needed for a full-stack feature.
- **Vue Context:** When working on the frontend, focus ONLY on `resources/js/` and `resources/css/`.
- **Zero-Redundancy:** Do not re-read `composer.json` or `package.json` once the stack is identified.

## 3. Structural Intelligence

- Use `anatomy.md` as your primary source for file locations.
- **Tool Parsimony:** Prefer one `grep` command over multiple `read_file` calls to find function definitions.
- **Diff-Only Reporting:** Only output the specific PHP methods or Vue components changed. [cite_start]Do not re-stream entire files[cite: 138].

## 4. Peak Hour Compliance (VET 07:00-13:00)

- During peak hours, strictly lead with actions. [cite_start]No conversational filler or long explanations of reasoning[cite: 111, 140].
