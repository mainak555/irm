# ADR-0001: Two-tier config split: JSON for identity, DB for runtime

**Date**: 2026-05-27
**Status**: accepted
**Deciders**: project team (modular-public-layout-with-config change)

## Context

The CMS has two sources of truth for site identity: a `settings` DB table holding key-value pairs (title, address, contact) and hardcoded strings scattered across PHP files. Admins who need to update the school name must either run a SQL UPDATE or edit PHP — neither is safe for a non-developer. At the same time, runtime content (news, menus, slides) changes frequently via the browser panel and must stay in the DB. We need to separate these two concerns cleanly.

## Decision

We split configuration into two tiers: `config/config.json` holds deployer-set identity and branding (title, address, colors, footer copy, quick links) and is never written by PHP at runtime. The MySQL DB holds admin-managed runtime content (news, menus, hero slides, content blocks). The `settings` and `quick_links` DB tables are dropped entirely.

## Alternatives Considered

### Alternative 1: Everything in JSON
- **Pros**: No DB dependency for any config; fully version-controllable.
- **Cons**: Admins edit news and menus frequently via the browser panel — JSON files are an awkward editing surface.
- **Why not**: Forces non-developers to hand-edit JSON for routine content updates.

### Alternative 2: Everything in DB
- **Pros**: Single source of truth; consistent admin UI for all data.
- **Cons**: The `settings` table is a fragile key-value store prone to schema drift; requires a DB connection just to get the school title for the `<title>` tag; not version-controllable.
- **Why not**: Two-source-of-truth problem is exactly what we are solving; file cache is faster for identity values that never change at runtime.

### Alternative 3: Keep settings table, sync to JSON on save
- **Pros**: Backward-compatible; no migration needed.
- **Cons**: Two sources of truth is the root problem — keeping both perpetuates it.
- **Why not**: Rejected for the same reason as Alternative 2.

## Consequences

### Positive
- School title, address, and branding are version-controllable and diff-able in git.
- Reading identity values requires no DB connection (faster, more resilient).
- A deployer with only text-editor access can change the school name safely.

### Negative
- Breaking migration required: `settings` and `quick_links` tables must be exported to JSON and then dropped.
- Deployers must know which data lives in JSON vs DB; documentation is essential.

### Risks
- One-time migration script must correctly export all existing `settings` rows before the tables are dropped — run and verify before deploying.
