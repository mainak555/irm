# ADR-0017: Theme pack CSS files discovered by filesystem scan, not manifest

**Date**: 2026-05-30
**Status**: accepted
**Deciders**: project team (json-config-settings change)

## Context

The admin General page (`admin/config_general.php`) needs to present a dropdown of available public-view CSS theme packs. Theme packs are CSS files that control the visual identity of the public-facing site. The active pack slug is stored in `config.json → public.theme` and applied at render time. We need a way to enumerate available packs so the dropdown can be populated.

## Decision

Available theme packs are discovered at request time by scanning `public/css/themes/*.css` with `glob()`. The filename stem (e.g., `classic` from `classic.css`) serves as both the slug stored in `config.json` and the display label (title-cased via `ucwords(str_replace('-', ' ', $slug))`). No manifest file is used.

## Alternatives Considered

### Alternative: Manifest file (`config/themes.json`)
- **Pros**: Explicit control over display names, ordering, and descriptions; could hold metadata like preview image paths.
- **Cons**: Adding a new pack requires two operations (drop CSS file + update manifest); manifest and filesystem can drift out of sync silently.
- **Why not**: This is a school CMS with a small number of hand-crafted packs, not a plugin marketplace. The sync risk outweighs the metadata benefit. Title-casing the slug produces an adequate label for the handful of packs this system will ever have.

## Consequences

### Positive
- Adding a new theme pack is a single operation: drop a `.css` file into `public/css/themes/`.
- No manifest/filesystem drift is possible — the dropdown always reflects what actually exists.
- Zero additional config files to maintain.

### Negative
- Display names are derived from filenames — multi-word labels require hyphenated filenames (e.g., `warm-earth.css` → "Warm Earth").
- Pack ordering is alphabetical (filesystem `glob()` order), not manually curated.

### Risks
- If `public/css/themes/` is missing or empty, the dropdown is empty and the saved slug may reference a non-existent file. Mitigation: `config_general.php` warns if the directory is empty; the public view falls back to `classic` if the active pack file is absent.
