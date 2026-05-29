# ADR-0011: Admin theme preference stored in database, not localStorage

**Date**: 2026-05-28
**Status**: accepted
**Deciders**: Mainak (project owner)

## Context

The admin UI supports three themes: light, dark, and system (follows OS preference). The user's chosen theme must be persisted somewhere so it survives page reloads. The two natural options are client-side localStorage (zero server involvement, instant) or a database column on `auth_users` (follows the user across devices and browsers). Admin users may legitimately use the admin from multiple devices (desktop, laptop, tablet) and expect a consistent experience.

## Decision

The `auth_users` table includes a `theme` column: `ENUM('light','dark','system') NOT NULL DEFAULT 'system'`. The chosen theme is stored here and loaded into `$_SESSION['auth']['theme']` at login. The `_layout.php` reads it to set `data-bs-theme` on the `<html>` element. When the user changes theme, a POST request updates the DB and refreshes the session value without a full page reload.

## Alternatives Considered

### Alternative: localStorage only (client-side)
- **Pros**: Zero server round-trip; works immediately without a DB column; no schema dependency
- **Cons**: Theme resets to default when browser storage is cleared; does not follow the user to other devices or browsers
- **Why not**: Admin users operating from multiple devices get an inconsistent experience; browser storage clearing is common in shared or managed environments

## Consequences

### Positive
- Theme preference is consistent across all devices and browsers for the same user
- Theme is available server-side at render time — no flash of unstyled content (FOUC) caused by a client-side JS theme-switcher running after page load
- Centralised preference storage alongside other user data

### Negative
- Changing theme requires a server round-trip (small POST) instead of a purely client-side update
- One additional column in `auth_users` that is not strictly "identity" data

### Risks
- **Risk**: Theme update request fails silently on slow connections → **Mitigation**: Show a flash confirmation ("Theme saved") so the user knows whether the preference was persisted
