# Edusfera UI Rules

This file defines mandatory design rules for all new and refactored UI in this project.

## Core Rules

1. Clear visual hierarchy
- One dominant heading per screen section.
- One supporting subheading that clarifies the heading.
- Everything else is secondary and must have lower visual weight.
- Avoid equal visual weight across many elements.

2. Short forms by default
- Prefer 2-3 primary fields per step where possible.
- Split long forms into logical steps/sections.
- Only request data required for the current action.
- Optional fields must be visually and semantically secondary.

3. Obvious primary action
- Every screen must expose one clear primary CTA above the fold.
- Primary CTA must be visually dominant and visible without searching.
- Do not hide key actions behind ambiguous labels or low-contrast controls.

4. Design with product logic
- Each block must answer: what user goal does it unblock?
- Remove decorative blocks that do not improve comprehension or conversion.
- Prefer clarity and speed over visual complexity.

## Edusfera-Specific Visual Constraints

- Palette baseline: `#000000` (black), `#7D39EB` (violet), `#C6FF33` (lime), `#FFFFFF` (white).
- Brand text `Edusfera` must use the project brand font (`Rimma Sans`).
- Forms must use a single visible border layer:
  - border on wrapper OR on input, but never both at once.
- Layout blocks should scale with viewport height:
  - use `dvh`-based min-height for full-screen auth/hero compositions.

## Implementation Checklist (Required)

- Hierarchy check:
  - one H1-like focus, one subtitle, reduced emphasis on tertiary content.
- Form check:
  - remove non-essential fields or move to later step.
- CTA check:
  - one dominant action immediately visible.
- Accessibility check:
  - focus states, readable contrast, keyboard reachable controls.
- Mobile check:
  - no clipped content, no hidden critical CTA, stable spacing on small heights.

## Applies To

- Public catalog and tutor pages.
- Filament auth pages (`/admin/login`, `/admin/register`).
- Filament dashboards and internal product flows.

