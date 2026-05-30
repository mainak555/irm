## Context

IRM has no way for admins to create public-facing content pages. `page.php` queries a `pages` DB table that was never built — it is dead code. All current public content lives in `index.php` as hardcoded PHP. School staff cannot publish new pages (Admission, Faculty, Results, etc.) without a developer editing PHP files.

This change introduces a visual page designer for `admin` and `sa` roles backed by filesystem JSON, a PHP renderer that serves those pages from a single public entry point, clean URL routing via `.htaccess`, and an admin menu manager.

**Constraints from the existing project:**
- Zero Composer / zero npm / zero build step — no new dependencies.
- `config/` is already Docker volume-mounted (`./config:/var/www/html/config`); page JSON files stored there survive container rebuilds without a new mount.
- `php:8.2-apache` is the Docker base image; `a2enmod rewrite` runs at build time but `AllowOverride` defaults to `None` — `.htaccess` rules are silently ignored without an explicit Apache vhost config.
- Content security: `html`-type columns let admins paste styled HTML. JavaScript must never be stored or rendered from admin input.

---

## Goals / Non-Goals

**Goals:**
- Admin can create, edit, and delete public pages stored as `config/public/{slug}.json`.
- Public URLs `/slug` resolve server-side rendered HTML (SEO-crawlable).
- Admin can manage `config/menu.json` with two-level nav items.
- Live right-side preview in the designer reflects changes as the admin edits.
- `<title>`, `<meta name="description">`, and `<link rel="canonical">` emitted from page JSON.
- `page.php` retired; `components/carousel.php` made self-contained.

**Non-Goals:**
- Sitemap.xml generation (deferred).
- OpenGraph / Twitter Card meta tags (deferred).
- Per-column width control (equal-width Bootstrap `col` only).
- More than two levels of nav depth.
- File upload within the designer (admin pastes URLs for embeds).
- DB-backed page storage — JSON is the sole approach.
- Any third-level nesting inside a component.

---

## Decisions

### D1 — Filesystem JSON over DB table for page storage

Pages are stored as `config/public/{slug}.json`. No `pages` DB table is created.

**Rationale:** The project's two-tier config principle places live content in JSON files under `config/`. DB storage adds a migration (schema change), a PDO helper, and a dependency on the DB connection for every public page render — even when the DB is not otherwise needed. JSON files on the existing Docker volume mount require no new infrastructure. Read performance for a PHP file-get-contents on a warm OS page cache is sub-millisecond — comparable to a primary-key DB lookup. If traffic outgrows this approach, the JSON schema maps cleanly to a DB table column-for-column.

**Rejected alternative:** `pages` DB table with a PDO singleton — heavier, adds a DB dependency for unauthenticated public page loads, requires a schema migration.

---

### D2 — Single-entry-point slug router (`index.php`)

`index.php` becomes a pure slug router. The `home` slug is reserved for the public home page. All designer pages — including home — are loaded from `config/public/{slug}.json`.

```
GET /about   →  .htaccess rewrites to index.php?p=about
                index.php reads config/public/about.json
                renders page via page_renderer.php
                404 if file missing
```

**Rationale:** A single entry point means one place to handle 404s, SEO meta tags, and the `require_once` of shell chrome. It avoids a proliferating set of `*.php` files for each public page. The `home` slug has no special-case logic — the router treats it identically to any other slug.

**Clean URL rewriting:**
```apache
# .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-z0-9-]+)/?$ index.php?p=$1 [L,QSA]
```

`php:8.2-apache` has `mod_rewrite` enabled but `AllowOverride None` by default — `.htaccess` is silently ignored without an Apache vhost override. A new `apache/000-default.conf` sets `AllowOverride All` for `/var/www/html` and is `COPY`-ed in the Dockerfile.

**Rejected alternative:** Individual `page.php?slug=X` style URLs — uglier, not SEO-friendly, requires the dead `page.php` to be refactored instead of deleted.

---

### D3 — Component self-containment contract

Components (`components/*.php`) are required to be fully self-contained. The renderer passes no variables. A component reads whatever it needs from its own `require_once` calls or directly from config/filesystem.

**Rationale:** A caller-injection contract tightly couples the renderer to each component's variable expectations. As new components are added (by any developer), the renderer would need constant updates. Self-containment is the same contract used by theme packs (ADR-0017) — drop a file, it works.

`carousel.php` currently reads `$layout` from caller scope. This variable dependency is removed as part of this change: the component always renders at `col-12` width (full-width), which is correct for its use inside a designer column.

**Rejected alternative:** Passing a `$context` array — adds boilerplate to every component and still requires renderer awareness.

---

### D4 — No `page` embed column type

The three column types are: `html`, `embed`, `component`. A fourth `page` type (embed one designer page inside another) was explicitly excluded.

**Rationale:** A `page` type creates a recursive render path. Even with a depth guard, the admin UI must prevent cycles (A embeds B which embeds A). The complexity is not justified — components provide equivalent composability for truly reusable content (carousel, noticeboard, etc.) without the recursion risk.

---

### D5 — JS stripping at save time, not render time

When an `html`-type column is saved, the PHP handler runs:
```php
$html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
$html = preg_replace('/\s+on\w+="[^"]*"/i', '', $html);
$html = preg_replace("/\s+on\w+='[^']*'/i", '', $html);
```
The stored JSON never contains `<script>` or `on*` event attributes. At render time the stored HTML is echoed raw (`echo $col['html']`) — same treatment as `body_html` in other admin-authored columns.

**Rationale:** Stripping at save time means the stored JSON is the auditable ground truth. Preview and production render identically because both read the same stored value. A render-time strip would still work, but adds a per-request cost and creates a discrepancy between what is stored and what is shown.

---

### D6 — Debounced live preview via POST to `page_preview.php`

The designer sends the current (unsaved) draft layout as JSON via `fetch` POST to `admin/page_preview.php` (debounced 500ms). The endpoint renders the layout server-side and returns HTML. The designer sets `previewIframe.srcdoc = responseHtml`.

**Rationale:** Server-side rendering for the preview guarantees preview/production parity — components, embed iframes, and SEO markup are identical to what the public sees. A client-side approximation (pure JS render) would drift. POST avoids URL-length limits that would constrain large page layouts.

**Rejected alternative:** Auto-save on every keystroke — too many writes; debounced fetch without saving is lower friction.

---

### D7 — Two-level nav only; Bootstrap dropdown

`config/menu.json` supports one level of `children` per top-level item. The admin menu manager enforces this limit. `includes/header.php` renders Bootstrap 5.3 `dropdown` markup when a top-level item has children; plain `<a>` otherwise.

**Rationale:** Three or more levels create accessibility and mobile UX problems. Bootstrap 5 has no native support for hover-triggered 3-level dropdowns without custom JS. Two levels cover every typical school website nav pattern.

---

## Page Layout JSON Schema

```jsonc
// config/public/{slug}.json
{
  "slug":        "about",
  "title":       "About Us",
  "description": "Learn about our institution — history, mission, and values.",
  "layout_id":   "landing",
  "slots": {
    "hero": {
      "type": "html",
      "html": "<h2>Our Mission</h2><p>...</p><a href=\"/admissions\">Apply</a>"
    },
    "highlight": {
      "type": "component",
      "name": "carousel"
    },
    "main": {
      "type": "embed",
      "subtype": "youtube",
      "url":     "https://www.youtube.com/embed/VIDEO_ID",
      "title":   "Welcome video"
    }
  }
}
```

- `layout_id` — references a page template inside `assets/css/layouts/{pack}/pages/{id}.php`.
- `slots` — keyed dict; each key is a placement ID defined by the layout template (e.g. `hero`, `main`, `sidebar`, `stat-1`).
- `type` — `html` | `embed` | `component`.
- For `embed`: `subtype` is `youtube` | `vimeo` | `pdf` | `mp4` | `website`.
- For `component`: `name` must match a filename stem in `components/`.
- Pages without `layout_id` fall back to a `rows`-based renderer (`render_page_layout()`) for backward compatibility.

---

## C4 Architecture

### System Context

```
┌─────────────────────────────────────────────────────────────────┐
│  IRM School CMS                                                 │
│                                                                 │
│  ┌───────────────┐  visits /about     ┌──────────────────────┐ │
│  │  Public       │──────────────────► │  index.php           │ │
│  │  Visitor      │  server-side HTML  │  (slug router)       │ │
│  └───────────────┘ ◄──────────────── └──────────────────────┘ │
│                                                ▲               │
│  ┌───────────────┐  builds pages via   ┌───────┴────────────┐  │
│  │  Admin /      │  designer UI        │  config/public/    │  │
│  │  SA user      │────────────────────►│  {slug}.json       │  │
│  └───────────────┘                     └────────────────────┘  │
│                        manages nav via  ┌────────────────────┐  │
│                        menu_manager     │  config/menu.json  │  │
│                        ────────────────►└────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Container: Public Request Flow

```
Browser GET /about
    │
    ▼ Apache mod_rewrite (.htaccess)
    │  RewriteRule ^([a-z0-9-]+)/?$ index.php?p=$1
    │
    ▼ index.php
    │  1. validate slug [a-z0-9-], reject reserved slugs
    │  2. read config/public/{slug}.json → 404 if missing
    │  3. set $page_title, $page_description, $canonical_url
    │  4. require includes/header.php   (layout pack header.php or fallback)
    │  5. call render_page($page_data)
    │  6. require includes/footer.php   (layout pack footer.php or fallback)
    │
    ▼ includes/page_renderer.php :: render_page(array $page_data)
       resolve layout_pack = cfg('public.layout')
       resolve page_file   = layouts/{pack}/pages/{layout_id}.php
       if page_file exists:
         $render_slot = fn(slot_id) => _render_col($slots[slot_id])
         require page_file   ← template calls ($render_slot)('hero') etc.
       else:
         render_page_layout($page_data['rows'] ?? [])  ← fallback (rows-based)

    ▼ _render_col(array $col)  — shared by both paths
         match $col['type']:
           'html'      → echo $col['html']  (pre-sanitized)
           'embed'     → render_embed($col)
           'component' → require components/{$col['name']}.php
```

### Container: Admin Designer Flow

```
Browser (page_designer.php)
    │
    ├── GET: load existing config/public/{slug}.json → populate form
    │         reads manifest layouts/{pack}/manifest.json → buildSlotPanels(layout_id)
    │         pre-fills slot panels from saved irmPageData.slots (matching IDs only)
    │
    ├── JS edit events (debounced 500ms)
    │       │
    │       └─► POST admin/page_preview.php
    │               body: { layout: { layout_id, slots: {...} } }
    │               response: { ok: true, html: "…" }
    │               designer sets previewIframe.srcdoc = html
    │
    └── POST (save): validate slug + layout_id + slots
                     strip <script> and on* from all html-type slots
                     write config/public/{slug}.json  { slug, title, description, layout_id, slots }
                     redirect to pages.php with flash
```

---

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| `.htaccess` silently ignored if `AllowOverride None` | Add `apache/000-default.conf` with `AllowOverride All`; COPY it in Dockerfile. Document in README §18. |
| XSS via `html`-type column | Strip `<script>` and `on*` attributes at save time. Stored JSON is the sanitized ground truth. |
| Slug collision with existing PHP files or directories | Block reserved slugs (`admin`, `assets`, `config`, `includes`, `components`, `api`) at save time; also check `!file_exists("{slug}.php")` against root. |
| Broken nav when page JSON is deleted | Menu manager warns when a page slug is linked in `menu.json`. Admin must decide — no auto-remove. |
| `components/` filesystem scan performance | `glob()` result is used only in the admin designer dropdown (not on every public page render). Public render uses `$col['name']` directly via `require`. |
| Component name traversal (`../../../etc/passwd`) | Sanitize `$col['name']` to `[a-z0-9_-]` before constructing the `require` path. Reject any component name containing `/` or `..`. |
| Circular / invalid preview on malformed draft JSON | `page_preview.php` validates the incoming JSON structure; returns `{ ok: false, msg: "..." }` on invalid input. Designer shows inline error toast. |
| Docker volume missing `config/public/` subdirectory | `index.php` and the admin pages create the directory with `mkdir($dir, 0755, true)` on first use. |

---

## Migration Plan

Ordered steps for deploying this change (each step is independently verifiable):

1. **Add Apache config** — create `apache/000-default.conf`, update `Dockerfile` with `COPY`. Rebuild image. Verify `.htaccess` is processed.
2. **Add `.htaccess`** — clean URL rewriting active. Existing `index.php` still works (rewrite condition excludes existing files).
3. **Fix `carousel.php`** — remove `$layout` caller-scope variable; always render `col-12`. Verify carousel still renders on current home page.
4. **Replace `index.php`** — becomes slug router. Current hardcoded home content is discarded. Create `config/public/home.json` with a starter single-row layout (one `component` column pointing to `carousel`).
5. **Delete `page.php`** — no inbound links exist. Verify 404 at `/page.php`.
6. **Upgrade `includes/header.php`** — add two-level Bootstrap dropdown support. Verify flat nav items still render correctly (no children array).
7. **Add `includes/page_renderer.php`** — function `render_page_layout()`.
8. **Deploy admin pages** — `admin/pages.php`, `admin/page_designer.php`, `admin/page_preview.php`, `admin/menu_manager.php`. Update `admin/_layout.php` sidebar with Content accordion.

**Rollback:** Steps 1–8 are independently reversible. JSON files under `config/public/` are the only new persistent state; they can be deleted to return to the pre-change state. Reverting `index.php` from git restores the old home page immediately.

---

## Open Questions

| # | Question | Default if unresolved |
|---|---|---|
| OQ-1 | Should `config/public/home.json` be seeded in `sql/schema.sql` (or a separate seed script) so first-time deployers get a usable home page? | Seed a minimal starter `home.json` with a single carousel component row. |
| OQ-2 | Canonical URL base: auto-detect from `$_SERVER['HTTP_HOST']` or require an `APP_URL` env var? | Auto-detect (`https://{HTTP_HOST}/{slug}`) — no extra env var. |
| OQ-3 | Should the designer support reordering rows and columns via drag-and-drop, or only up/down buttons? | Up/down buttons only (no extra JS library). |
| OQ-4 | `menu.json` `url` field for external links vs `slug` field for designer pages — should the schema disambiguate these explicitly, or infer from `is_external`? | Explicit: `slug` (internal designer page) OR `url` (external/static); `is_external` flag derived from which field is set. |
