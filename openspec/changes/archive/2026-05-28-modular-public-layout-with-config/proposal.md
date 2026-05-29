## Why

The public-facing home page (`index.php`) is a monolithic file that mixes layout structure, branding text, menu items, and carousel wiring into a single document — making it impossible to reuse the header, footer, or menu across future pages without copy-paste duplication. All static text (school title, address, welcome copy, external links) is either hardcoded in PHP or stored in a `settings` DB table, meaning a deployer must touch the database to change the school name. This change modularises the layout into composable includes and externalises all static identity/content into JSON config files so pages are assembled, not written.

## What Changes

- **BREAKING**: Remove `settings` DB table and `setting()` helper — replaced by `config/config.json` read via a new `cfg()` helper in root `config.php`.
- **BREAKING**: Remove `quick_links` DB table — quick links (footer) now live in `config/config.json`.
- Extract `<header>` band + primary nav into `includes/header.php` (reusable include).
- Extract `<footer>` into `includes/footer.php` (reusable include).
- Extract menu data out of DB — public nav reads `config/menu.json` directly; DB `menus` table remains for admin editing but is not queried on public page renders.
- Create `config/` folder with five JSON files:
  - `config.json` — school identity: title, subtitle, address, phone, email, social URLs, color scheme, fonts, footer copy, copyright, quick links.
  - `menu.json` — top-level nav items; primary runtime nav source and DB seed.
  - `home.json` — all static content for the home page: `about`, welcome block, typed sections array, partner labels.
  - `external_links.json` — "Most Popular Links" entries (label + URL); deployer-managed, not loaded by index.php directly.
  - `slides.json` — hero carousel caption overlay and JSON-only slide entries; replaces DB `hero_slides` overlay.
- Unify `cfg()`, `h()`, and `menu_url()` helpers into root `config.php` — no separate `includes/config.php`.
- Carousel reads image files directly from `assets/img/carousel/` folder; `config/slides.json` provides optional caption overlay and extra slides.
- Rewrite `index.php` to assemble the page from includes and read all static text via `cfg()` / `home.json`.
- Home page sections rendered via a typed array (`type: text | video | noticeboard | links`) — component types `noticeboard` and `links` render placeholder divs for future injection.
- `index.html` static mockup updated to mirror the same modular structure for offline preview.

## Capabilities

### New Capabilities

- `static-config`: JSON-driven site identity and branding — `cfg()`, `h()`, and `menu_url()` helpers unified in root `config.php`; `config/config.json` schema; dot-notation access; CSS custom-property injection from config values in header.
- `modular-layout`: Reusable `includes/header.php` (JSON nav, DB-free) and `includes/footer.php` that any public page can `require_once` to get the full chrome without duplication.
- `home-content-config`: `config/home.json` holding all home-page static text; typed sections array (`type: text|video|noticeboard|links`) drives layout; `noticeboard` and `links` types render component placeholder divs. `config/external_links.json` exists as a deployer file but is not loaded by `index.php` directly — the `links` section is a component placeholder.
- `carousel-folder`: Carousel auto-discovers images from `assets/img/carousel/` via PHP `glob()`; `config/slides.json` provides optional caption overlay and extra JSON-only slides (replaces DB overlay).
- `menu-seed-json`: `config/menu.json` as the runtime nav source for public pages and DB seed on fresh install.

### Modified Capabilities

_(none — no existing specs exist; all capabilities above are net-new)_

## Impact

- **Deleted**: `includes/functions.php → setting()`, `h()`, `menu_url()` helpers (moved to `config.php`); `includes/config.php` (unified into `config.php`); `sql/schema.sql` `settings` and `quick_links` tables.
- **New files**: `config/config.json`, `config/menu.json`, `config/home.json`, `config/external_links.json`, `config/slides.json`, `assets/img/carousel/` folder.
- **Modified**: `config.php` (gains `cfg()`, `h()`, `menu_url()`), `includes/header.php`, `includes/footer.php`, `index.php`, `sql/schema.sql`.
- **Admin**: `admin/settings.php` no longer writes to a `settings` table — it writes back to `config.json` (or is retired in favour of direct file editing; to be decided in design).
- **Existing pages**: `page.php` will benefit immediately from the reusable header/footer includes without further changes.
- **No Composer / npm dependencies added.**
