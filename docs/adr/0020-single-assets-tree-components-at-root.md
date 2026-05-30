# ADR-0020: Single assets tree — components at root level

**Date**: 2026-05-30
**Status**: accepted
**Deciders**: project team

## Context

The project had grown two separate homes for static files (`assets/css/` and
`public/css/themes/`) and placed PHP include partials under `public/components/`,
mixing "web-accessible static files" semantics with PHP includes under the same
`public/` root. The admin stylesheet lived at `admin/style.css`, a third location.

This caused:
- Dual source-of-truth for CSS: deployers had to know about both `assets/` and `public/`.
- The name `public/` falsely implied a security boundary (browser-only) in a project where
  the entire docroot IS the project root — PHP includes there are no safer than anywhere else.
- Docker volume mounts for user-customisable assets were split across `public/` and `assets/`.

## Decision

All static files (CSS, images) live under a single **`/assets/`** tree:

```
assets/
  css/
    site.css          — public site stylesheet
    admin.css         — admin Material Shadcn theme (was admin/style.css)
    themes/           — public theme pack CSS files (was public/css/themes/)
      classic.css
  img/
    logo.png
    carousel/         — Docker volume mount
    gallery/          — Docker volume mount (when gallery ships)
```

PHP include partials live under **`/components/`** at the project root (was
`public/components/`). The `public/` directory is removed entirely.

## Alternatives Considered

### Alternative: Keep public/ as docroot alias
Move Apache DocumentRoot to `/public/` (Laravel-style hardened docroot).
- **Pros**: Real separation between web-accessible and non-web-accessible files.
- **Cons**: Major structural refactor; all includes, configs, and relative URLs would
  need rewiring. Over-engineered for a project that has no sensitive PHP files in the docroot.
- **Why not**: The project already blocks `.env` and `config/` access via server config.
  A full docroot move is scope far beyond the problem.

## Consequences

### Positive
- Single place to look for all static assets.
- `docker-compose` volume mounts are all under `/assets/img/` — easy to reason about.
- `public/` removal eliminates the misleading security-boundary implication.
- Adding a theme pack is `assets/css/themes/<name>.css` — consistent with other assets.

### Negative
- Any bookmark or hardcoded link to `/public/css/themes/…` or `/admin/style.css` breaks.
  Mitigation: these are internal server-side paths only (never user-facing URLs), so
  breakage scope is limited to this codebase.

### Files changed
| Old path | New path |
|---|---|
| `public/css/themes/*.css` | `assets/css/themes/*.css` |
| `admin/style.css` | `assets/css/admin.css` |
| `public/components/carousel.php` | `components/carousel.php` |

### PHP call sites updated
| File | Change |
|---|---|
| `includes/header.php` | theme file path + `<link>` URL |
| `admin/_layout.php` | admin CSS `<link>` URL |
| `admin/config_general.php` | `$themes_dir` path + UI hint strings |
| `index.php` | carousel `require` path |
