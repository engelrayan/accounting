---
name: Frontend rules — Blade / CSS / JS separation
description: Strict rules for how frontend code must be structured in the Accounting module
type: feedback
---

No inline CSS, no `<style>` tags, and no JavaScript logic inside any Blade file.

**Why:** The user enforces a strict separation of concerns so Blade files stay clean HTML-only templates.

**How to apply:**
- All CSS → `public/css/accounting.css`
- All JS  → `public/js/accounting.js`
- Blade files contain only HTML structure and Tailwind/custom class names
- Any interactivity must be written in `accounting.js` and referenced via `id` or `data-*` attributes on the element
- Never add `onclick=""`, `style=""`, or `<script>` blocks to a Blade file
