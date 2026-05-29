# ADR-0005: admin/settings.php writes config.json directly, dropping settings DB table

**Date**: 2026-05-27
**Status**: accepted
**Deciders**: project team (modular-public-layout-with-config change)

## Context

With school identity moving to `config/config.json` (ADR-0001), the admin settings page must have a way to persist changes made via the browser panel. The previous approach wrote to the `settings` DB table, which is now being dropped. We need a write path that keeps JSON as the single source of truth without introducing a second source.

## Decision

`admin/settings.php` reads `config/config.json`, merges the submitted form fields, writes a backup to `config/config.json.bak`, then writes the updated file using `file_put_contents` with `LOCK_EX`. After writing, it validates the result with `json_decode`; if validation fails it restores the backup and shows an error flash. The `settings` DB table is dropped and not kept in sync.

## Alternatives Considered

### Alternative 1: Keep settings table, sync to JSON on each admin save
- **Pros**: DB remains the write source; JSON is a derived cache — familiar pattern.
- **Cons**: Two sources of truth is the root problem (ADR-0001). If they diverge (e.g., direct DB edit), behaviour is unpredictable.
- **Why not**: Syncing defeats the purpose of moving to JSON; ADR-0001 explicitly rejects this.

### Alternative 2: Admin panel retired — JSON edited manually only
- **Pros**: Zero write-path code; no file-permission concerns.
- **Cons**: Non-developer admins lose the ability to update school contact details via browser.
- **Why not**: The admin UI must remain usable by non-developers; this requirement is non-negotiable.

## Consequences

### Positive
- `config.json` remains the single source of truth — no sync logic needed.
- The `.bak` copy provides a one-step rollback if a bad save corrupts the file.
- `LOCK_EX` prevents file corruption under concurrent saves (two admins saving simultaneously).

### Negative
- The web server process must have write permission on `config/` — requires `chmod 664` or equivalent; must be documented.
- Concurrent writes are serialised by `LOCK_EX` but the second writer overwrites the first silently — acceptable for a single-admin school site.

### Risks
- `config/config.json` malformed by a bad admin save → site renders with defaults. Mitigated by post-write JSON validation and `.bak` restore.
- File-permission misconfiguration on a shared host → silent write failure. Mitigated by showing an error flash when `file_put_contents` returns false.
