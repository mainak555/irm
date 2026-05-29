## Context

The CMS currently has two sources of truth for site identity: a `settings` DB table (title, address, contact) and hardcoded strings scattered across `index.php` and `includes/header.php`. A deployer changing the school name must either run a SQL UPDATE or edit PHP files — neither is safe for a non-developer. Meanwhile, header and footer markup is duplicated across every public page, meaning layout changes require touching multiple files.

Architecture diagram: `architecture.drawio` (open in draw.io)

**Current state:**
- `index.php` — 180-line monolith: layout + content + DB queries
- `includes/header.php` — mixes `<head>` meta with nav query; not reusable across page variants
- `includes/footer.php` — reads from `settings` + `quick_links` DB tables
- No `config/` folder exists
- Carousel image paths are hardcoded in DB slides or inline in PHP

**Constraints:**
- Zero Composer, zero npm — all solutions must be pure PHP + vanilla JS/CSS
- PHP 8.0+ minimum; `declare(strict_types=1)` on every file
- No secrets in JSON files — DB credentials stay in `.env` only
- `h()` wraps all config values rendered to HTML

## Goals / Non-Goals

**Goals:**
- Single `require_once 'includes/header.php'` gives any page the full header chrome
- Single `require_once 'includes/footer.php'` gives any page the full footer
- All school identity text (title, address, welcome copy, link labels) lives in `config/*.json` — editable with any text editor, no DB access required
- Carousel slides auto-discovered from `assets/img/carousel/` folder — no DB entry or code change needed to add a new image
- `admin/settings.php` writes changes back to `config.json` so the admin UI still works for non-developers

**Non-Goals:**
- Full WYSIWYG editor for JSON files in the admin panel
- Multi-tenant or per-page config overrides
- Removing DB support for carousel (DB slides with captions remain supported alongside folder discovery)
- Changing the visual design or CSS variables

## Decisions

### D1: Two-tier config split (JSON for identity, DB for runtime)

**Decision:** `config.json` holds deployer-set identity (title, address, colors, quick links). The DB holds admin-managed runtime content (news, slides, menus, blocks).

**Why not put everything in JSON?** Admins edit news and menus frequently via the browser panel — JSON files are awkward for that. But school title and address change once per deployment and don't need a UI.

**Why not keep everything in DB?** The `settings` table is a key-value store that's fragile to schema drift and requires a DB connection just to get the school title for the `<title>` tag. JSON is faster (file cache) and version-controllable.

### D2: Unified `config.php` — cfg(), h(), menu_url() in one place

**Decision:** Root `config.php` exposes three pure helpers alongside env/DB config: `cfg(string $key, mixed $default = null)` for dot-notation access into `config.json`, `h(?string $s)` for HTML escaping, and `menu_url(array $m)` for building nav hrefs. No separate `includes/config.php` exists. Every file that loads `config.php` (directly or via `db.php → config.php`) has all three helpers available.

**Why not keep `includes/config.php`?** It was a one-function file that required a separate `require_once` on every include. Unifying into root `config.php` eliminates the extra include and the risk of loading `h()` and `menu_url()` only in some code paths.

**Why put `h()` in config.php and not functions.php?** `h()` is needed by `header.php` and `footer.php` which no longer load `functions.php` (they are DB-free). Moving it to `config.php` — which is always the first file loaded — makes it unconditionally available.

**Why static cache vs. opcode/APCu?** No external dependencies. PHP's opcode cache already caches the file parse across requests on most hosts. Static variable is sufficient for within-request deduplication.

### D3: Carousel folder discovery with optional DB overlay

**Decision:** `index.php` uses `glob('assets/img/carousel/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE)` to list slides. If a DB hero slide exists with a matching filename, its caption is used; otherwise the filename (without extension) is used as the caption. DB slides with paths outside the folder are still rendered.

**Why not glob-only?** Admins may want custom captions per image. Keeping DB slides working means no data migration is needed.

**Why not DB-only?** The original requirement: "drop a file in the folder and it appears". Zero-friction for the school office.

### D4: `home.json` typed sections array with component placeholders

**Decision:** `config/home.json` holds: `about` (short intro string), `welcome` block, `section_cols` (integer layout hint), `sections` as an ordered array of objects — each with `type: text|video|noticeboard|links`, `heading`, `body`, and type-specific fields (`img` for text, `provider`+`url` for video). Partners live in a separate `partners` array rendered in their own strip below sections.

The `sections` array replaces the previous named-key object (`sections.life`, `sections.eco`, `sections.tour`). Ordering, count, and column layout are all data-driven — a deployer adds or reorders sections by editing JSON, not PHP.

**Component placeholder pattern:** Section types `noticeboard` and `links` render a `<div class="component-placeholder" data-component="…">` — a forward-compatible injection point for future dynamic components. `index.php` does not load `external_links.json` or any DB data for these types yet.

**`section_cols`:** An integer hint (e.g., `4`) carried as `data-cols` on the section container. CSS can respond to it now or via a future grid enhancement; PHP does not need to change when the column count changes.

**Why not route through `cfg()`?** `cfg()` loads `config.json`. Home content is large and page-specific; loading it on every page (including `page.php`) would be wasteful. `index.php` loads only what it needs.

### D5: `admin/settings.php` writes `config.json` directly

**Decision:** The admin settings form POSTs to `admin/settings.php` which opens `config.json`, merges the submitted fields, and writes it back with `json_encode(..., JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)`. The `settings` DB table is dropped.

**Risk accepted:** Concurrent writes could corrupt the file. Mitigation: use `file_put_contents` with `LOCK_EX` flag.

**Alternative considered:** Keep `settings` table, sync to JSON on save. Rejected — two sources of truth is the problem we are solving.

### D6: CSS custom properties injected from `config.json` inside `<head>`

**Decision:** `includes/header.php` reads `cfg('colors')` and emits an inline `<style>:root { --cream: ...; --link: ...; }</style>` block immediately after the `<link>` to `site.css`. This makes all brand colors overridable without editing CSS.

**Why inline `<style>` not a generated CSS file?** No file-write step needed on each request; inline styles are cached by the browser as part of the HTML. For a low-traffic school site this is fine.

### D7: Public nav reads menu.json directly — no DB query on page render

**Decision:** `includes/header.php` reads `config/menu.json` on every request to build the `<nav>`. The `menus` DB table is not queried during public page rendering. Admin panel changes to menus must write back to `menu.json` to take effect on the public site (implementation deferred to admin panel scope).

**Why not DB-first with JSON fallback?** The original design had a try/catch that queried DB first and fell to JSON only on failure. This meant any public page load made a DB round-trip just to render the nav — a fixed cost even when the DB has nothing dynamic to add. Removing the query makes the home page fully DB-free: no connection is opened unless a DB function is explicitly called elsewhere on the page.

**Risk accepted:** Nav changes in the admin panel won't appear on the public site until `menu.json` is also updated. This is acceptable for a school site where nav structure rarely changes and deployments are manual.

### D8: slides.json replaces DB hero_slides overlay for carousel

**Decision:** `config/slides.json` (array of `{image_path, caption}` objects) takes the role previously held by the `hero_slides` DB table: it provides caption overrides for folder-discovered images and declares JSON-only slides. `index.php` reads this file instead of querying `hero_slides`.

**Why?** Consistent with the overall direction of making the home page DB-free. `hero_slides` captions change as infrequently as other static content; a deployer can edit `slides.json` as easily as touching the DB. The DB table can be retired when the admin carousel panel is re-scoped.

## Risks / Trade-offs

| Risk | Mitigation |
|------|-----------|
| `config.json` malformed by bad admin save → site blank | Validate JSON after write in `admin/settings.php`; keep a `.bak` copy before overwriting |
| File permissions: web server cannot write `config.json` | Document in README that `config/` must be writable (`chmod 664` or equivalent) |
| `glob()` returns images in OS-defined order (not alphabetical on all systems) | Sort the array with `natsort()` after glob |
| Carousel folder grows large → slow glob on every page load | Acceptable for a school site; add `apcu_` cache if needed in future |
| `home.json` and `external_links.json` not editable via admin UI | Mark as deployer-only files in README; admin UI for these is out of scope |
| Dropping `settings` and `quick_links` tables is a breaking migration | Provide a one-time migration script that exports existing DB values to `config.json` |
