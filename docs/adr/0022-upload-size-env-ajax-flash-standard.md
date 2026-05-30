# ADR-0022: Upload Size via Env Var + AJAX Flash Standard

**Date**: 2026-05-30
**Status**: accepted
**Deciders**: Mainak Chowdhury

## Context

Two related problems surfaced when adding upload progress feedback to the Carousel admin page:

1. **Upload size limit was hard-coded** as `5 * 1024 * 1024` in `carousel.php`. Any future upload page would repeat this constant, creating multiple places to change when the limit is adjusted. The `.env` file is already the single source of truth for all runtime configuration.

2. **AJAX actions had no consistent feedback pattern.** Form POST → redirect flows use `$_SESSION['flash']`, rendered by `_layout.php`. But fetch-based uploads ended with `location.reload()`, which bypassed the session flash entirely and gave no per-file success/error summary. The "Add User" flow shows inline modal errors but then reloads silently on success — not usable for batch operations where per-item results matter.

## Decision

### Upload size
Read `UPLOAD_MAX_BYTES` from `.env` via `env()` with a default of `5242880` (5 MB). Pass the same value to JavaScript as a PHP-rendered constant so client-side pre-validation uses the identical limit. No page or script may hard-code a byte limit.

### AJAX flash standard
AJAX handlers that end with `location.reload()` must write a flash to `sessionStorage` before reloading:
```js
sessionStorage.setItem('irmFlash', JSON.stringify({ type: 'ok'|'err', msg: '...' }));
location.reload();
```
`_layout.php` reads `irmFlash` on every page load, injects a Bootstrap alert into `#flashArea`, then clears the key. This renders exactly once, survives the reload, and is visually identical to the PHP session flash.

### Upload handler JSON contract
AJAX upload endpoints must detect `X-Fetch: 1` and return `{ok, msg}` JSON on **both success and error** (never a redirect). The `upload_reply(bool $ok, string $msg, bool $is_fetch, string $filename)` helper in `carousel.php` is the reference implementation.

## Alternatives Considered

### Alternative 1: PHP config constant for upload size
- **Pros**: No env file needed for a single constant.
- **Cons**: Constants in PHP source files are not deployment-config — they require a code change to adjust per environment. `.env` is already established for all other runtime knobs.
- **Why not**: Inconsistent with the existing `env()` pattern.

### Alternative 2: Store AJAX flash in `$_SESSION` via a separate endpoint
- **Pros**: No sessionStorage; fully server-side.
- **Cons**: Requires an extra round-trip, adds complexity, and flash is lost if the browser navigates elsewhere before the session write completes.
- **Why not**: sessionStorage is per-tab, cleared on read, and simpler for client-initiated reloads.

### Alternative 3: DOM-patch the filmstrip without reloading
- **Pros**: No full page reload; faster perceived performance.
- **Cons**: Requires duplicating the PHP filmstrip rendering in JavaScript and keeping both in sync.
- **Why not**: Over-engineering for a low-frequency admin action. Full reload is simpler and always consistent.

## Consequences

### Positive
- `UPLOAD_MAX_BYTES` in `.env` is the single place to change the limit for all current and future upload features.
- Batch uploads (multiple files) produce a clear `x uploaded · y failed` summary instead of silently reloading.
- The sessionStorage → `_layout.php` injection is available to all admin pages — any page doing a fetch + reload gains consistent flash display for free.
- Client-side pre-validation (file too large) fires before any network request, reducing wasted uploads.

### Negative
- sessionStorage is browser-local; server-side logging of upload outcomes still requires explicit error logging in PHP.

### Risks
- `sessionStorage` is cleared when the tab closes. If the user closes the tab between `setItem` and `location.reload()` (a race that cannot happen in practice), the flash is lost. Acceptable.
