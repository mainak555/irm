# ADR-0004: Separate home.json and external_links.json for page-specific static copy

**Date**: 2026-05-27
**Status**: accepted
**Deciders**: project team (modular-public-layout-with-config change)

## Context

`index.php` contains large blocks of static welcome copy, section headings, and a "Most Popular Links" list that never changes at runtime. Currently these are either hardcoded strings in PHP or stored in `content_blocks` / `popular_links` DB tables — requiring a DB round-trip to retrieve text that is effectively constant. Loading all of this via `cfg()` (which reads `config.json`) would force every page, including `page.php`, to pay the cost of parsing home-page-specific content it never uses.

## Decision

`config/home.json` holds all home-page static copy: welcome heading, welcome body paragraphs, life/eco/tour section headings and bodies, partner labels. `config/external_links.json` holds the "Most Popular Links" list. Both are read directly by `index.php` via `json_decode(file_get_contents(...), true)` — not routed through `cfg()`. Neither file is writable via the admin panel; they are deployer-only.

## Alternatives Considered

### Alternative 1: Route everything through cfg() / config.json
- **Pros**: Single config access pattern across all files.
- **Cons**: Forces every page (including `page.php`) to load and parse home-page-specific content — wasteful. `config.json` would become very large.
- **Why not**: `cfg()` is designed for identity/branding values shared across all pages, not page-specific copy.

### Alternative 2: Keep static copy in DB content_blocks table
- **Pros**: Editable via admin panel without a file edit.
- **Cons**: Requires DB connection and query to retrieve text that never changes; subject to accidental admin edits; not version-controllable.
- **Why not**: Static copy that changes only on redeployment belongs in version-controlled files, not a DB table.

## Consequences

### Positive
- `index.php` loads only the data it needs; `page.php` is not burdened with home content.
- Welcome copy and popular links are version-controllable and diff-able.
- No DB query needed for purely static text.

### Negative
- School staff cannot edit welcome copy via the admin panel — requires a file edit and redeployment.
- Two additional files to document and maintain (`home.json`, `external_links.json`).

### Risks
- Staff may not know these files exist or how to edit them — must be documented clearly in README as deployer-only files.
