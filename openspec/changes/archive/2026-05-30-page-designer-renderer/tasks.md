## 1. Apache / Docker infrastructure

- [x] 1.1 Create `apache/000-default.conf` with `<VirtualHost *:80>`, `DocumentRoot /var/www/html`, and `<Directory /var/www/html> AllowOverride All </Directory>`
- [x] 1.2 Add `COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf` to `Dockerfile` (after existing `RUN a2enmod rewrite` line)
- [x] 1.3 Create `.htaccess` at web root with `RewriteEngine On`, skip-files condition, skip-dirs condition, and `RewriteRule ^([a-z0-9-]+)/?$ index.php?p=$1 [L,QSA]`

## 2. carousel.php — self-containment fix

- [x] 2.1 Remove the `$_layout = $layout ?? 'full'` line and the `match ($_layout) { … }` block from `components/carousel.php`
- [x] 2.2 Replace `$_col_class` usage with the literal `col-12` in the outer `<div>` of `components/carousel.php`

## 3. includes/page_renderer.php — core renderer

- [x] 3.1 Create `includes/page_renderer.php` with `declare(strict_types=1)` and `function render_page_layout(array $layout): void`
- [x] 3.2 Implement the rows → cols loop: emit `<div class="row">` per row, `<div class="col">` per column, close both divs
- [x] 3.3 Implement `html` column branch: echo `$col['html'] ?? ''` (no escaping — content is sanitised at save time)
- [x] 3.4 Implement `embed` column branch with subtype dispatch: `youtube`/`vimeo` → `<iframe src="…">`, `pdf` → `<object data="…">`, `mp4` → `<video controls src="…">`, `website` → `<iframe src="…">`; escape all URLs with `h()`
- [x] 3.5 Implement `component` column branch: sanitise `$col['name']` to `[a-z0-9_-]`, construct path `__DIR__ . '/../components/' . $name . '.php'`, `require` if exists, else emit `<div class="irm-component-missing">[{$name}]</div>`
- [x] 3.6 Handle unknown column type: emit empty `.col` div, no fatal error

## 4. Public routing — index.php slug router

- [x] 4.1 Replace `index.php` entirely: read `$slug = $_GET['p'] ?? 'home'`, validate against `[a-z0-9-]+`, check reserved-slug list
- [x] 4.2 On reserved slug or invalid characters: send HTTP 404 header and exit with a simple not-found message
- [x] 4.3 Read `config/public/{slug}.json`; send HTTP 404 if file does not exist
- [x] 4.4 Decode JSON and extract `$page_title`, `$page_description`, `$canonical_url` (construct from `$_SERVER['HTTP_HOST']` + slug)
- [x] 4.5 Emit `<meta name="description">` only when `description` is non-empty; emit `<link rel="canonical">` unconditionally
- [x] 4.6 Call `render_page_layout($layout['rows'] ?? [])` between `require_once includes/header.php` and `require_once includes/footer.php`
- [x] 4.7 Create `config/public/home.json` with a starter layout: one row containing one `component` column (`"name": "carousel"`)
- [x] 4.8 Delete `page.php`
- [x] 4.9 Update `config/config.json` footer `quick_links` entries that reference `page.php?slug=contact` — replace with clean URL slugs (e.g. `/contact`)

## 5. includes/header.php — two-level Bootstrap dropdown nav

- [x] 5.1 Add `$item['children'] = $item['children'] ?? []` normalisation inside the existing `foreach ($nav_items as &$item)` loop
- [x] 5.2 Replace the flat `<ul>/<li>/<a>` nav loop with a conditional: if `$item['children']` is non-empty render Bootstrap dropdown markup (`nav-item dropdown`, `data-bs-toggle="dropdown"`, `<ul class="dropdown-menu">`); otherwise render plain `<li><a>` as before
- [x] 5.3 Render child items as `<li><a class="dropdown-item" href="…">Label</a></li>` inside the dropdown `<ul>`
- [x] 5.4 Apply `active` class to the parent `<li>` when `$active_slug` matches either the parent slug or any child slug
- [x] 5.5 Apply `target="_blank" rel="noopener"` to any item (top-level or child) with `is_external == 1`

## 6. Admin sidebar — Content accordion

- [x] 6.1 Add a **Content** `<li>` accordion group to `admin/_layout.php` sidebar (positioned after Settings accordion, before Authorization accordion)
- [x] 6.2 Add "Pages" sub-item (`href="pages.php"`) visible to `admin` and `sa`; mark active when `$_SERVER['PHP_SELF']` matches `pages.php` or `page_designer.php`
- [x] 6.3 Add "Menu Manager" sub-item (`href="menu_manager.php"`) visible to `sa` only (wrap in `if ($user['role'] === 'sa')`)

## 7. admin/pages.php — page list

- [x] 7.1 Create `admin/pages.php` with `require_auth('admin', 'sa')` and standard admin layout pattern
- [x] 7.2 Scan `config/public/*.json` with `glob()`; for each file decode JSON and collect slug, title, `filemtime`
- [x] 7.3 Render a Bootstrap table with columns: Title, Slug, Last Modified, Actions (Edit / Delete)
- [x] 7.4 Render empty-state card with "Create your first page" call-to-action when no pages found
- [x] 7.5 Handle POST delete: validate CSRF, check for slug in `config/menu.json` (include warning in confirm), unlink file, flash redirect

## 8. admin/page_designer.php — designer UI

- [x] 8.1 Create `admin/page_designer.php` with `require_auth('admin', 'sa')` and standard admin layout pattern
- [x] 8.2 On GET with `?slug`: read `config/public/{slug}.json`, populate `$page_data`; slug field rendered read-only
- [x] 8.3 On GET without `?slug`: initialise `$page_data` to empty (slug editable, one empty row + one empty column)
- [x] 8.4 Render slug, title, and description fields above the row builder
- [x] 8.5 Render each row as a card; inside, render each column as a nested card with a type `<select>` (`html` / `embed` / `component`) and type-specific fields shown/hidden via JS
- [x] 8.6 For `html` columns: render a `<textarea>` for raw HTML input
- [x] 8.7 For `embed` columns: render a sub-type `<select>` and a URL `<input>` with a dynamic hint label
- [x] 8.8 For `component` columns: render a `<select>` populated from PHP scan of `components/*.php` stems
- [x] 8.9 Add JS for row management: "Add Row" appends a new row card; "Remove Row" (with confirm if non-empty) removes it; "Move Up" / "Move Down" swaps adjacent rows
- [x] 8.10 Add JS for column management: "Add Column" appends a column card (disabled when 4 present); "Remove Column" removes it (hidden when only 1 present)
- [x] 8.11 Add JS for live preview: `debounce(collectLayout, 500)` → `fetch` POST to `admin/page_preview.php` → set `previewIframe.srcdoc`; on `{ok: false}` show inline error toast
- [x] 8.12 On POST (save): validate CSRF; validate slug `[a-z0-9-]`, not in reserved list, no existing file collision (create mode only); strip `<script>` and `on*` from all `html` column values; `mkdir config/public/` if absent; write JSON; flash redirect to `admin/pages.php`

## 9. admin/page_preview.php — preview endpoint

- [x] 9.1 Create `admin/page_preview.php` with `require_auth()`, check `$_SERVER['HTTP_X_FETCH']`
- [x] 9.2 Decode POST body as JSON; return `{"ok":false,"msg":"Invalid layout"}` if `json_decode` fails
- [x] 9.3 Call `render_page_layout()` wrapped in `ob_start()` / `ob_get_clean()`; return `{"ok":true,"html":"…"}`

## 10. admin/menu_manager.php — menu CRUD

- [x] 10.1 Create `admin/menu_manager.php` with `require_auth('sa')` and standard admin layout pattern
- [x] 10.2 Read `config/menu.json` (default to `[]` if absent); sort by `sort_order`; render top-level items with children indented
- [x] 10.3 Render an add/edit form for top-level items: label, internal-slug vs external-URL toggle, sort_order; on POST validate CSRF, merge into items array, rewrite file
- [x] 10.4 Render an add/edit form for child items (only shown nested under a top-level item); same fields; enforce no grandchild UI
- [x] 10.5 Implement delete for top-level item: confirm dialog warns if item has children; warns if `config/public/{slug}.json` exists; on POST validate CSRF, remove item + children, rewrite file
- [x] 10.6 Implement delete for child item: warns if `config/public/{slug}.json` exists; on POST validate CSRF, remove child, rewrite file
- [x] 10.7 Implement reorder (move up / move down) for top-level items: swap `sort_order` values of adjacent items, rewrite file; same for children within their parent
- [x] 10.8 Create `config/menu.json` with `[]` if it does not exist on first write

## 11. Verification

- [x] 11.1 Verify `/` loads `home.json` designer page with carousel component rendered
- [x] 11.2 Verify `/about` returns 404 before `config/public/about.json` exists; returns page content after it is created
- [x] 11.3 Verify a reserved slug (`/admin`) returns HTTP 404
- [x] 11.4 Verify existing static assets (`/assets/css/site.css`) are NOT rewritten by `.htaccess`
- [x] 11.5 Verify `<title>`, `<meta name="description">`, and `<link rel="canonical">` are emitted correctly for a designer page
- [x] 11.6 Verify `html` column with `<script>alert(1)</script>` has the script stripped after save; inline styles are preserved
- [x] 11.7 Verify nav renders plain links for items without children; Bootstrap dropdown for items with children
- [x] 11.8 Verify `page.php` returns 404 (file deleted)
- [x] 11.9 Verify carousel renders at full width (col-12) as a component column
