# ADR-0015: Audit Columns UI Display Standard

**Date**: 2026-05-29
**Status**: accepted
**Deciders**: Mainak Chowdhury

## Context

Every table carries four standard audit columns: `created_at`, `updated_at`, `created_by`, `updated_by`. Two UI decisions had to be made:

1. **Which columns to surface in the UI.** Showing all four (Created + Updated) in table columns and detail pages consumes horizontal space and adds visual noise. In most admin workflows the most relevant audit fact is "who last changed this and when."

2. **How to format timestamps.** UTC values must be converted to the browser's local timezone. The browser API (`Intl.DateTimeFormat` with `timeZoneName: 'short'`) appends a timezone label such as `GMT+5:30` or `EST`. These labels eat width in table cells and `GMT+N:NN` offsets are visually noisy without communicating DST state clearly.

## Decision

**Show only `updated_at` / `updated_by` in the admin UI.** `created_at` and `created_by` are stored in the database and available for queries but are not surfaced in any UI component (table columns, form footers, detail panels).

**Display timestamps without an inline timezone label.** The inline text shows date and time only (e.g. `29 May 2026, 02:30`). The full context — local time with unambiguous IANA timezone name plus the raw UTC value — is available on hover via the `title` tooltip (e.g. `29 May 2026, 02:30 (Asia/Kolkata)  ·  2026-05-29 21:00:00 UTC`).

## Alternatives Considered

### Alternative 1: Show both Created and Updated columns
- **Pros**: Complete audit trail visible at a glance
- **Cons**: Two extra columns in already wide tables; "Created" is rarely the actionable piece of information in day-to-day admin work
- **Why not**: Column width budget and relevance — updated information is almost always what an admin needs

### Alternative 2: Show inline timezone abbreviation (original implementation)
- **Pros**: DST state visible without hover
- **Cons**: `GMT+5:30`-style offsets are cryptic and long; abbreviated names (`EST`, `CST`) are ambiguous across regions; consumes significant horizontal space in narrow table cells
- **Why not**: Tooltip puts the unambiguous IANA name (`America/New_York`, `Asia/Kolkata`) one hover away with zero layout cost

### Alternative 3: Show timezone name inline instead of abbreviation
- **Pros**: Unambiguous
- **Cons**: IANA names like `America/New_York` are very long — worse than the abbreviation for layout
- **Why not**: Same layout problem, solved better by the tooltip approach

## Consequences

### Positive
- Leaner table layouts — one fewer column per table
- Tooltip provides richer, unambiguous timezone context (IANA name + raw UTC) than an inline abbreviation ever could
- Standard is simple to apply consistently: one `data-utc-ts` attribute, auto-handled by the global JS utility

### Negative
- `created_at` / `created_by` are not visible in the UI; admins who want creation date must query the DB directly

### Risks
- Future tables may need creation date surfaced for business reasons. Mitigation: `created_at`/`created_by` are always stored — surfacing them is a one-line HTML addition and does not require schema changes.
