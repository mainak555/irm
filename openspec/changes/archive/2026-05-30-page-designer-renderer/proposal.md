## Why

The CMS has no way for admins to build or edit public-facing content pages — `page.php` queries a `pages` DB table that was never created, making it dead code. School staff cannot publish pages like Admission, Faculty, or Results without hand-editing PHP or JSON files. The page designer gives `admin` and `sa` roles a visual, no-code tool to build and publish pages instantly.

## What Changes

- **BREAKING**: `page.php` is retired. The planned `pages` DB table is never created. All public content pages are JSON-based designer pages stored under `config/public/{slug}.json`.
- `index.php` becomes a slug router (`?p=slug`) — the home page (`home` slug) and all other designer pages are served from a single entry point.
- `.htaccess` added for clean URL rewriting (`/about` → `index.php?p=about`). A new `apache/000-default.conf` sets `AllowOverride All` so `.htaccess` takes effect inside Docker.
- `includes/header.php` upgraded to render a two-level Bootstrap dropdown nav (top-level + one `children` level from `menu.json`).
- `components/carousel.php` fixed to be fully self-contained (remove `$layout` variable dependency from caller scope).
- New `includes/page_renderer.php` — PHP renderer that walks page layout JSON rows → columns and dispatches by column type (`html`, `embed`, `component`).
- New `config/public/` directory (covered by existing `./config` Docker volume mount — no new mount needed).
- New admin pages: `admin/pages.php` (list, `admin`+`sa`), `admin/page_designer.php` (create/edit, `admin`+`sa`), `admin/page_preview.php` (POST preview endpoint), `admin/menu_manager.php` (menu CRUD, `sa` only).
- Admin sidebar updated with a new **Content** accordion containing Pages and Menu Manager items.
- SEO: `<title>`, `<meta name="description">`, and `<link rel="canonical">` emitted from page JSON on every designer page. Sitemap and OG tags deferred.

## Capabilities

### New Capabilities

- `page-designer`: Admin UI for creating and editing designer pages — slug entry, row/column builder, column type selector with per-type fields, right-side live preview (debounced POST, rendered via `srcdoc`). Accessible to `admin` and `sa`.
- `page-renderer`: PHP function `render_page_layout()` in `includes/page_renderer.php`. Walks rows → columns, dispatches `html` (strip `<script>`/`on*`, echo raw), `embed` (sub-type dropdown: youtube/vimeo → `<iframe>`, pdf → `<object>`, mp4 → `<video>`, website → `<iframe>`), `component` (filesystem-scanned `components/*.php`, self-contained). No `page` embed type.
- `public-routing`: `index.php` slug router + `.htaccess` clean URLs + `apache/000-default.conf`. Slug `[a-z0-9-]` only. Reserved slugs blocked. 404 when `config/public/{slug}.json` missing. SEO meta tags emitted from page JSON.
- `menu-manager`: Admin UI for `config/menu.json` CRUD. Two levels only (top-level + `children`). Warns when deleting a menu item whose page JSON exists. `sa` only.

### Modified Capabilities

- `modular-layout`: `includes/header.php` nav rendering changes from a flat `<ul>` to a two-level Bootstrap dropdown. Requirement change: nav must support a `children` array per item and render Bootstrap dropdown markup when children are present.
- `menu-seed-json`: The `children` array is already in the spec schema. No requirement change — only the menu manager admin UI is new (covered by `menu-manager` capability above).

## Impact

| Area | Change |
|---|---|
| `index.php` | Becomes slug router — current hardcoded home content discarded |
| `page.php` | Deleted |
| `includes/header.php` | Two-level dropdown nav |
| `includes/page_renderer.php` | New file |
| `components/carousel.php` | Remove `$layout` caller-scope dependency |
| `config/public/` | New directory; covered by existing Docker volume mount |
| `apache/000-default.conf` | New file; `COPY`-ed in Dockerfile; sets `AllowOverride All` |
| `.htaccess` | New file; clean URL rewrites |
| `admin/_layout.php` | New **Content** sidebar accordion |
| `admin/pages.php` | New — page list |
| `admin/page_designer.php` | New — designer UI |
| `admin/page_preview.php` | New — POST preview endpoint |
| `admin/menu_manager.php` | New — menu CRUD |
| `Dockerfile` | Add `COPY apache/000-default.conf` |
