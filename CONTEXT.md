# IRM Admin

The admin panel for a self-hosted school CMS. Manages users, content, and authentication configuration.

## Language

### Admin Navigation

**Configurations**:
The sub-item under the Authorization accordion linking to `admin/auth_config.php`. Manages the OIDC/SAML authentication provider. Distinguished from General (site identity) by scope: Configurations = who can log in; General = what the site looks like.
_Avoid_: Settings (ambiguous — Settings is a top-level accordion), Auth Settings

**Settings** (accordion):
The top-level sidebar accordion visible to `admin` and `sa` roles. Contains sub-items for site-wide configuration stored in JSON files: General (sa-only) and Carousel (admin + sa). Distinct from Authorization which manages users and auth providers.
_Avoid_: Config menu, Admin settings

**General**:
The sub-item under the Settings accordion linking to `admin/config_general.php`. Restricted to `sa` role only. Edits `config/config.json → general.*` fields (title, subtitle, logoUrl, address, phone, fax, email, social.facebook) and the active theme pack slug (`public.theme`). The `colors` and `footer` sections of config.json are out of scope for this page.
_Avoid_: Basic Configuration, Site Settings, Settings

**Carousel**:
The sub-item under the Settings accordion linking to `admin/carousel.php`. Accessible to `admin` and `sa` roles. Manages carousel slides — uploads images to `assets/img/carousel/` and writes captions to `config/slides.json`. Images dropped directly into the folder (e.g. at deploy time) also appear without requiring an admin action.
_Avoid_: Slider, Hero, Banner

**`slides.json`**:
A flat JSON object at `config/slides.json` mapping image filename (e.g. `"main_building.jpg"`) to a caption string. Written by the admin Carousel page. Read by the public carousel component. Images with no entry in `slides.json` render with no caption. Replaces the previous array-of-objects schema.
_Avoid_: slides array, hero_slides

**`general` (config key)**:
The top-level key in `config/config.json` that holds school identity and contact fields. Replaces the previous `school` key — all `cfg('school.*')` calls become `cfg('general.*')`. This rename is a **BREAKING** change tracked in the json-config-settings proposal.
_Avoid_: `school` key (deprecated)

**Theme pack**:
A named CSS file under `assets/css/themes/` (e.g., `classic.css`, `modern.css`) that controls the public view's visual identity. The active pack slug is stored in `config.json → public.theme` and read via `cfg('public.theme')`. Available packs are discovered by scanning `assets/css/themes/*.css` at request time — no manifest file. Distinct from the admin theme (light/dark/system) stored per-user in `auth_users.theme`.
_Avoid_: template, skin, public theme (use "theme pack" as the noun phrase)

### Page Designer

**Designer page**:
A public-facing content page whose layout is stored as `config/public/{slug}.json`. Built via the admin page designer. Distinct from static PHP pages (`index.php`). Replaces the previously planned DB-based `pages` table concept entirely — `page.php` is retired.
_Avoid_: content page, CMS page, static page

**Page layout**:
The JSON structure inside `config/public/{slug}.json` describing a page's content as an ordered list of rows, each containing 1–4 equal-width columns. Each column has a `type` that determines how it renders. The `home` slug is reserved for the public home page.
_Avoid_: page template, page schema, layout config

**Row**:
A Bootstrap grid row inside a page layout. Contains 1–4 columns of equal width (Bootstrap auto `col`). Rows stack vertically in document order.
_Avoid_: section, band, block

**Column**:
A single cell within a row. Has exactly one `type`: `html`, `embed`, or `component`. Equal-width within its row.
_Avoid_: cell, slot, widget

**Column type**:
Determines how a column renders. Valid values: `html` (admin-authored HTML, JS stripped), `embed` (iframe/video/object with a sub-type), `component` (a PHP partial from `components/`).
_Avoid_: block type, widget type

**Embed sub-type**:
The specific media kind for a column of type `embed`. Values: `youtube` → `<iframe>` (embed URL), `vimeo` → `<iframe>` (embed URL), `pdf` → `<object>`, `mp4` → `<video>` (external URL only), `website` → `<iframe>`. Admin selects from a dropdown and pastes the URL.
_Avoid_: media type, embed kind

**Component**:
A self-contained PHP partial under `components/*.php` that renders itself with no variables injected by the caller. Discovered at runtime by filesystem scan — adding a file to `components/` makes it available in the designer dropdown immediately. Distinct from shell chrome (header, footer, nav) which is never a component.
_Avoid_: widget, plugin, module

**Shell chrome**:
`includes/header.php` and `includes/footer.php` — always rendered on every public page, outside the designer's control. The primary nav is part of shell chrome and managed separately via the menu manager.
_Avoid_: layout chrome (use shell chrome), wrapper

**Slug**:
The URL path segment and filename stem for a designer page. Pattern: `[a-z0-9-]` only. Maps bidirectionally: URL `/about` ↔ file `config/public/about.json`. Reserved slugs that cannot be used: `admin`, `assets`, `config`, `includes`, `components`, `api`. Duplicate slugs are blocked with an error.
_Avoid_: page name, page id, URL key

**Unlisted page**:
A designer page that exists in `config/public/` but has no corresponding entry in `config/menu.json`. Reachable by direct URL, not visible in navigation. Used for drafting or auxiliary pages.
_Avoid_: hidden page, draft page, orphan page

**Menu manager**:
The admin page (`admin/menu_manager.php`) restricted to `sa` role. Manages `config/menu.json` — add, edit, reorder, and delete nav items with optional one level of children. Separate from page creation.
_Avoid_: nav editor, navigation settings

**Page designer**:
The admin page (`admin/page_designer.php`) accessible to `admin` and `sa` roles. Builds and edits designer page layouts row by row, column by column. Includes a right-side live preview pane (debounced POST to preview endpoint, rendered via `srcdoc`).
_Avoid_: layout editor, page builder, page editor

**Page renderer**:
The PHP function in `includes/page_renderer.php` that reads a page layout array and outputs server-side HTML. Dispatches by column type. Strips `<script>` and `on*` attributes from `html`-type content at render time.
_Avoid_: layout renderer, template engine

### User Management

**Sentinel**:
The one bootstrap `sa` account identified by `email = 'admin' AND role = 'sa'`. It is created at setup, cannot be modified or deleted by anyone, and bypasses all permission checks.
_Avoid_: super admin, god user, root user

**Creator**:
The authenticated user whose `id` is stored in `created_by` at the time a row is inserted. Ownership is immutable — role changes do not transfer it.
_Avoid_: owner, author

**Creator-scoped**:
A permission model where full CRUD on a same-rank peer is granted only when the acting user is the peer's creator. Cross-rank actors (higher rank → lower rank) are never restricted by creator-scoping.
_Avoid_: owner-based, creator-owned

**Peer row**:
A same-rank user row that the logged-in user did not create. Rendered with `irm-peer-row` styling: active toggle remains operable, all other controls are disabled and the action menu is absent.
_Avoid_: locked row, read-only row

**Role rank**:
A numeric ordering of roles used for hierarchy checks: `sa`=3, `admin`=2, `faculty`=1, `user`=0. A higher-rank actor may freely act on any lower-rank target regardless of creator-scoping.
_Avoid_: role level, role weight

**Re-assign on delete**:
Before a user row is deleted, all rows whose `created_by` equals the deleted user's `id` are updated to `created_by = deleter_id`. Prevents orphaned rows.
_Avoid_: ownership transfer, cascade reassign
