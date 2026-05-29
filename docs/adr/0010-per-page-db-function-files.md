# ADR-0010: Per-page DB function files instead of a monolithic db.php

**Date**: 2026-05-28
**Status**: accepted
**Deciders**: Mainak (project owner)

## Context

The project uses a PDO singleton (`db()` from `includes/db.php`) shared across all pages. As the admin grows to cover many domains (auth, content management, news, slides, links, etc.), all DB queries need to live somewhere. The options are a single large file, a single file per domain group, or a file per admin page. The existing public-side pattern already has `includes/functions.php` as a shared utility file but it is not query-focused.

## Decision

Each admin page has its own DB function file named `includes/db_<page>.php` (e.g., `db_login.php`, `db_profile.php`, `db_auth_config.php`). Each file contains only the query functions needed by its corresponding admin page. Admin pages `require_once` their own DB file and call its functions directly. The shared PDO singleton (`db()`) remains in `includes/db.php` and is called by all DB function files.

## Alternatives Considered

### Alternative A: Single db_auth.php for all auth-related functions
- **Pros**: Fewer files; all auth queries in one place
- **Cons**: As the admin grows, the file becomes a catch-all; unclear which page uses which function
- **Why not**: Grouping by domain ("auth") is still too coarse — login, profile, and OIDC config have different query patterns and change independently

### Alternative B: Monolithic includes/db.php for all queries
- **Pros**: Single file to look at for all queries
- **Cons**: Grows unbounded; merging new admin pages requires modifying a shared file that touches everything
- **Why not**: At scale, the file becomes a maintenance burden and a source of merge conflicts

## Consequences

### Positive
- Adding a new admin page means adding one new `db_<page>.php` — no shared files modified
- Each file is small and focused; easy to read and test in isolation
- Clear ownership: `admin/news.php` → `includes/db_news.php`

### Negative
- More files in `includes/` as the admin grows (one per page)
- Functions that could be shared between pages must either be duplicated or extracted into a shared utility

### Risks
- **Risk**: Common queries (e.g., user lookup) duplicated across multiple db_*.php files → **Mitigation**: If duplication becomes a problem, extract truly shared queries into a `db_shared.php`; do not over-engineer upfront
