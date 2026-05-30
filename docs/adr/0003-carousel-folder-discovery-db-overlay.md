# ADR-0003: Carousel folder discovery with optional DB caption overlay

**Date**: 2026-05-27
**Status**: superseded by ADR-0018 (2026-05-30)
**Deciders**: project team (modular-public-layout-with-config change)

> **Superseded by [ADR-0018](0018-slides-json-flat-object-replaces-db-overlay.md).** The DB `hero_slides` overlay described here was replaced by a `config/slides.json` flat object. The `glob()` discovery pattern was retained; the DB join was not.

## Context

Carousel slides are currently managed as DB rows with hardcoded image paths, meaning adding a new slide requires either a DB INSERT or a code edit — neither is accessible to school office staff. The requirement is "drop a file in a folder and it appears". At the same time, some slides have admin-curated captions that must not be lost.

## Decision

`index.php` uses `glob('assets/img/carousel/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE)` to discover slide images, sorted with `natsort()`. If a DB `hero_slides` row exists with a filename matching a glob result, its caption is used; otherwise the filename (extension stripped, underscores replaced with spaces, title-cased) is used as the caption. DB slides whose paths do not match any folder image are still rendered, preserving externally-hosted or admin-only slides.

## Alternatives Considered

### Alternative 1: Glob only, no DB overlay
- **Pros**: Zero DB dependency for carousel; dead-simple.
- **Cons**: No way for admins to set per-image captions without editing filenames; existing DB captions are silently discarded.
- **Why not**: Admins have invested effort in captions; data migration is avoidable.

### Alternative 2: DB only, require DB entry for every slide
- **Pros**: All carousel data in one place; captions always explicit.
- **Cons**: Adding a slide still requires a DB INSERT — the "drop a file and it appears" requirement is not met.
- **Why not**: Zero-friction file-drop is the core requirement.

## Consequences

### Positive
- School staff can add a slide by copying an image file — no DB access needed.
- Existing admin-curated captions are preserved without migration.
- `natsort()` gives deterministic order across Linux and Windows file systems.

### Negative
- Caption derivation from filenames is a lossy heuristic — filenames must be chosen thoughtfully.
- Union of glob + DB slides requires a deduplication step (match by filename).

### Risks
- `glob()` order is OS-defined without `natsort()` — already mitigated.
- Large carousel folder (100+ images) could slow glob on every page load; acceptable for a school site. APCu cache can be added later if needed.
