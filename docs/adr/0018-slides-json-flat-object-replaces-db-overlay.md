# ADR-0018: slides.json flat object replaces DB caption overlay

**Date**: 2026-05-30
**Status**: accepted — supersedes ADR-0003 (caption mechanism only; folder discovery unchanged)
**Deciders**: carousel-config change

## Context

ADR-0003 introduced `config/slides.json` as an array of `{image_path, caption}` objects overlaid on top of the `hero_slides` DB table. As the admin panel gains a carousel management UI, the DB overlay adds unnecessary complexity — the admin UI is now the authoring path. The array schema also requires an O(n) scan to find a caption by filename, and supports "JSON-only slides" (entries pointing to images outside the carousel folder) which are no longer needed.

## Decision

`config/slides.json` is redefined as a flat JSON object keyed by image basename: `{"campus.jpg": "Our Campus"}`. Caption lookup is O(1) by `basename($path)`. JSON-only slides are no longer supported — all visible slides must physically exist in `assets/img/carousel/`. The admin carousel UI reads and writes this file directly.

## Alternatives Considered

### Alternative 1: Keep array-of-objects schema
- **Pros**: Preserves insertion order explicitly; already specced in carousel-folder.
- **Cons**: O(n) scan to find a caption by filename; admin UI must iterate to find or update an entry; more verbose to write.
- **Why not**: The admin UI makes filename-keyed lookup the natural access pattern; insertion order is irrelevant since glob+natsort determines slide order, not slides.json.

### Alternative 2: Move captions back to DB
- **Pros**: Transactional writes; consistent with other admin-managed content.
- **Cons**: Requires a new DB table; loses the "mount /config for backup" Docker model; adds DB dependency to the carousel component.
- **Why not**: JSON stays consistent with the two-tier config model (ADR-0001); /config volume mount covers backup and portability.

## Consequences

### Positive
- Caption lookup is O(1): `$captions[basename($image)]` instead of array iteration.
- Admin UI write is a simple key-value set, not array manipulation.
- Empty `slides.json` is `{}` — trivially valid; current `[]` migrates with a one-liner.
- Removes ambiguity of JSON-only slides (no longer supported).

### Negative
- BREAKING change: any existing reader of the old array format must be updated.
- Insertion order is implicit (filename-sorted by glob+natsort), not explicit in the JSON.

### Risks
- Concurrent admin writes to `slides.json` (two admins editing simultaneously) could corrupt the file — acceptable for a school CMS where simultaneous admin sessions are rare; file locking can be added later if needed.
