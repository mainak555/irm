# ADR-0002: cfg() helper with dot-notation and per-request static cache

**Date**: 2026-05-27
**Status**: accepted
**Deciders**: project team (modular-public-layout-with-config change)

## Context

`config/config.json` needs to be readable from any PHP file — header, footer, admin pages — without each caller repeating `json_decode(file_get_contents(...))`. The config object is nested (school identity, colors, footer), so a flat key API like `cfg('school_title')` would either duplicate the nesting in key names or lose structure. We also want to avoid reading the file on every call within a single request.

## Decision

`includes/config.php` exposes a single function `cfg(string $key, mixed $default = null): mixed`. Keys use dot-notation (`cfg('school.title')`, `cfg('footer.quick_links')`). The parsed JSON array is cached in a `static` variable so `file_get_contents` is called at most once per PHP request.

## Alternatives Considered

### Alternative 1: Flat keys (school_title, footer_quick_links)
- **Pros**: Simpler implementation; no path traversal logic needed.
- **Cons**: Forces `config.json` to be a flat object, losing semantic grouping; key names become long and inconsistent.
- **Why not**: Dot-notation keeps the JSON grouped by concern without flattening everything.

### Alternative 2: APCu / opcode shared-memory cache
- **Pros**: Cache survives across requests; faster on high-traffic sites.
- **Cons**: Requires APCu extension; adds an external dependency; overkill for a school site with low traffic.
- **Why not**: No external dependencies is a hard constraint. PHP's opcode cache already caches file parses across requests on most hosts; static variable handles within-request deduplication.

### Alternative 3: Global variable / singleton object
- **Pros**: Accessible anywhere without a function call overhead.
- **Cons**: Global state is harder to test and reason about; pollutes the global namespace.
- **Why not**: A named function is more explicit and testable than a magic global.

## Consequences

### Positive
- Any PHP file gets config values with a single readable call: `cfg('school.title')`.
- JSON structure is preserved — config.json can be organized semantically.
- Zero file I/O after the first call within a request.

### Negative
- Config is loaded lazily on first call; if `config.json` is missing or malformed, the error surfaces at render time rather than at boot.

### Risks
- Malformed `config.json` will cause `json_decode` to return null, and all `cfg()` calls will return their `$default`. Add a startup check in `includes/config.php` that calls `error_log()` if the file is unreadable or unparseable.
