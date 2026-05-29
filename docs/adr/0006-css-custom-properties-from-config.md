# ADR-0006: CSS custom properties injected inline from config.json in header.php

**Date**: 2026-05-27
**Status**: accepted
**Deciders**: project team (modular-public-layout-with-config change)

## Context

The site's brand colors are currently hardcoded in `site.css`. A deployer changing the school's color scheme must edit a CSS file — a non-trivial task that risks breaking other selectors. Since `config/config.json` already holds brand identity (ADR-0001), colors should live there too and be applied without a CSS file edit.

## Decision

`includes/header.php` reads `cfg('colors')` and emits an inline `<style>:root { --cream: …; --link: …; }</style>` block inside `<head>`, immediately after the `<link>` to `site.css`. Each key in the `colors` object becomes a CSS custom property. All values are passed through `h()` before output. `site.css` uses `var(--cream)` etc. throughout — no color literals remain in the stylesheet.

## Alternatives Considered

### Alternative 1: Generate a static CSS file on each admin save
- **Pros**: Browser caches the CSS file separately; no inline style in HTML.
- **Cons**: Requires a file-write step on each config save; adds complexity to `admin/settings.php`; generated file must be excluded from git or treated specially.
- **Why not**: For a low-traffic school site, inlining a small `:root {}` block is simpler and the browser caches the full HTML page anyway.

### Alternative 2: Keep colors hardcoded in site.css, document them
- **Pros**: Zero runtime logic; CSS file is self-contained.
- **Cons**: A color change still requires editing a CSS file — inaccessible to a non-developer deployer; violates the goal of JSON-driven branding.
- **Why not**: Defeats the purpose of centralising identity in `config.json`.

### Alternative 3: Serve a PHP-generated CSS file (header("Content-Type: text/css"))
- **Pros**: Fully cacheable by CDN/browser as a CSS resource.
- **Cons**: Adds a separate PHP entry point; more complex cache invalidation; overkill for a school site.
- **Why not**: Complexity not justified for the traffic level.

## Consequences

### Positive
- A deployer changes brand colors by editing one JSON file — no CSS knowledge required.
- Colors remain version-controllable alongside other identity values.
- CSS custom properties cascade naturally; all existing `var()` references work without modification.

### Negative
- Inline `<style>` block in every HTML response adds a small amount of HTML weight (typically < 200 bytes).
- If `cfg('colors')` returns an empty array (malformed config), no custom properties are emitted and the page falls back to CSS file defaults.

### Risks
- A color value containing `</style>` could break the inline block — mitigated by wrapping all values with `h()` before output.
