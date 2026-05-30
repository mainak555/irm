## ADDED Requirements

### Requirement: render_page dispatcher
`includes/page_renderer.php` SHALL expose `render_page(array $page_data): void` as the primary public entry point. It SHALL read `cfg('public.layout')` to resolve the active layout pack, sanitise `$page_data['layout_id']` to `[a-z0-9-]`, locate `assets/css/layouts/{pack}/pages/{layout_id}.php`, and include it with a `$render_slot` callable in scope. If the page template file does not exist or `layout_id` is empty, it SHALL fall back to `render_page_layout($page_data['rows'] ?? [])`.

#### Scenario: Slot-based page rendered via layout template
- **WHEN** `render_page` is called with `layout_id: "landing"` and a populated `slots` dict
- **THEN** the function SHALL include `assets/css/layouts/{pack}/pages/landing.php` with `$render_slot` callable injected

#### Scenario: Fallback to rows renderer when no layout_id
- **WHEN** `render_page` is called with an empty `layout_id`
- **THEN** the function SHALL call `render_page_layout($page_data['rows'] ?? [])` and SHALL NOT throw an error

#### Scenario: Path traversal in layout_id rejected
- **WHEN** `layout_id` contains `../` or other non-`[a-z0-9-]` characters
- **THEN** the sanitised value SHALL not resolve outside the `pages/` directory

---

### Requirement: render_page_layout function
`includes/page_renderer.php` SHALL also expose `render_page_layout(array $rows): void` for backward compatibility with pages that use the rows-based schema. It walks the `$rows` array and outputs Bootstrap-grid HTML. It SHALL require no global state.

#### Scenario: Function accepts valid rows and produces output
- **WHEN** `render_page_layout` is called with an array containing one row with one html column
- **THEN** the function SHALL echo HTML containing a Bootstrap `.row` div wrapping a `.col` div containing the column content

#### Scenario: Empty rows renders nothing
- **WHEN** `render_page_layout` is called with `[]`
- **THEN** the function SHALL echo nothing and SHALL NOT throw an error

---

### Requirement: Bootstrap grid output
For each entry in the `$rows` array, the renderer SHALL emit `<div class="row">`. For each column in the row's `cols` array, it SHALL emit `<div class="col">`. All columns within a row SHALL be equal-width (Bootstrap auto `col`). The renderer SHALL close both divs after emitting column content.

#### Scenario: One row two columns produces correct markup
- **WHEN** `render_page_layout` is called with one row containing two columns
- **THEN** the output SHALL contain one `<div class="row">` wrapping two `<div class="col">` elements

#### Scenario: Multiple rows stack vertically
- **WHEN** `render_page_layout` is called with three rows
- **THEN** the output SHALL contain three `<div class="row">` elements in document order

---

### Requirement: HTML column rendering
For a column with `type` equal to `html`, the renderer SHALL echo the stored `html` value directly without additional escaping. The stored value is guaranteed to be sanitised at save time (no `<script>`, no `on*` attributes).

#### Scenario: HTML column echoes stored content verbatim
- **WHEN** the stored column html is `<h2>Welcome</h2><p style="color:#333">Hello</p>`
- **THEN** the rendered output SHALL contain that exact string

#### Scenario: Empty html column renders empty col
- **WHEN** a column has `type` `html` and an empty or missing `html` value
- **THEN** the renderer SHALL emit the wrapping `.col` div with no inner content

---

### Requirement: Embed column rendering
For a column with `type` equal to `embed`, the renderer SHALL dispatch on the `subtype` field and emit the appropriate HTML element. YouTube and Vimeo subtypes SHALL use `<iframe>` with a responsive wrapper. PDF subtype SHALL use `<object>`. MP4 subtype SHALL use `<video controls>`. Website subtype SHALL use `<iframe>`. The URL value SHALL be escaped with `h()` before output.

#### Scenario: YouTube embed renders iframe
- **WHEN** a column has `type` `embed`, `subtype` `youtube`, and a valid embed URL
- **THEN** the rendered output SHALL contain an `<iframe>` element with the URL as the `src` attribute (escaped)

#### Scenario: Vimeo embed renders iframe
- **WHEN** a column has `type` `embed`, `subtype` `vimeo`, and a valid embed URL
- **THEN** the rendered output SHALL contain an `<iframe>` element with the URL as the `src` attribute

#### Scenario: PDF embed renders object
- **WHEN** a column has `type` `embed`, `subtype` `pdf`, and a URL
- **THEN** the rendered output SHALL contain an `<object data="…">` element

#### Scenario: MP4 embed renders video element
- **WHEN** a column has `type` `embed`, `subtype` `mp4`, and a URL
- **THEN** the rendered output SHALL contain a `<video controls>` element with the URL as the `src`

#### Scenario: Website embed renders iframe
- **WHEN** a column has `type` `embed`, `subtype` `website`, and a URL
- **THEN** the rendered output SHALL contain an `<iframe>` element with the URL as the `src` attribute

#### Scenario: Unknown subtype renders nothing
- **WHEN** a column has `type` `embed` with an unrecognised `subtype` value
- **THEN** the renderer SHALL emit the wrapping `.col` div with no inner content and SHALL NOT throw an error

---

### Requirement: Component column rendering
For a column with `type` equal to `component`, the renderer SHALL sanitise the `name` field to `[a-z0-9_-]` characters only, construct the path `components/{name}.php`, and `require` that file. If the file does not exist, the renderer SHALL emit a visible error placeholder instead of crashing.

#### Scenario: Valid component name loads file
- **WHEN** a column has `type` `component` and `name` `carousel`, and `components/carousel.php` exists
- **THEN** `render_page` / `render_page_layout` SHALL include `components/carousel.php` and its output SHALL appear inside the `.col` div

#### Scenario: Component name sanitised before path construction
- **WHEN** a column has `name` value containing `../` or other path-traversal sequences
- **THEN** the sanitised name SHALL contain only `[a-z0-9_-]` characters, and the resulting path SHALL not resolve outside `components/`

#### Scenario: Missing component renders placeholder
- **WHEN** a column has `type` `component` and `name` `nonexistent`, and no matching file exists
- **THEN** the renderer SHALL emit a placeholder such as `<div class="irm-component-missing">[nonexistent]</div>` and SHALL NOT throw a fatal error

---

### Requirement: Preview endpoint
`admin/page_preview.php` SHALL accept a POST request with a JSON body containing a `layout` key with `layout_id` and `slots` fields. It SHALL call `render_page()` with the submitted page data, capture the output via `ob_start()` / `ob_get_clean()`, and return a JSON response `{ "ok": true, "html": "…" }`. On invalid JSON or structural validation failure it SHALL return `{ "ok": false, "msg": "…" }`. It SHALL require an authenticated session (`require_auth()`).

#### Scenario: Valid slot-based layout returns rendered HTML
- **WHEN** an authenticated admin POSTs `{ "layout": { "layout_id": "full-width", "slots": { "main": { "type": "html", "html": "<p>Hi</p>" } } } }` to `admin/page_preview.php`
- **THEN** the response SHALL be `{ "ok": true, "html": "…" }` where html contains `<p>Hi</p>` rendered inside the layout template

#### Scenario: Invalid JSON returns error
- **WHEN** the POST body is not valid JSON
- **THEN** the response SHALL be `{ "ok": false, "msg": "Invalid layout" }`

#### Scenario: Unauthenticated request rejected
- **WHEN** a request arrives at `admin/page_preview.php` without an active admin session
- **THEN** the response SHALL redirect to `admin/login.php` (standard `require_auth()` behaviour)
