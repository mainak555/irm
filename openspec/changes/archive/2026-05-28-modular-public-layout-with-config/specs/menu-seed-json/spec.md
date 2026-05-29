## ADDED Requirements

### Requirement: menu.json schema
`config/menu.json` SHALL be a JSON array of top-level nav item objects. Each object SHALL have: `label` (string, display text), `slug` (string, URL path or anchor), `sort_order` (integer, ascending sort priority). Optionally each object MAY have a `children` array of the same structure for one level of sub-items. This file is declarative and SHALL be valid JSON.

#### Scenario: File decodes to an array
- **WHEN** `config/menu.json` is read and decoded
- **THEN** it SHALL be a PHP array (not an object)

#### Scenario: Every top-level item has required fields
- **WHEN** each element in the decoded array is inspected
- **THEN** every element SHALL have non-empty string keys `label` and `slug`, and an integer key `sort_order`

#### Scenario: Items are sortable by sort_order
- **WHEN** the array is sorted ascending by `sort_order`
- **THEN** items with lower `sort_order` values SHALL appear first in the nav

### Requirement: menu.json is the runtime nav source for public pages
`includes/header.php` SHALL read `config/menu.json` directly on every public page render to build the primary navigation. No DB query SHALL be made by `includes/header.php`. The `menus` DB table remains the admin-editable source of truth; admin panel changes to menus MUST also update `menu.json` to take effect on the public site.

#### Scenario: Nav renders from menu.json
- **WHEN** `config/menu.json` contains active nav items
- **THEN** `includes/header.php` SHALL render those items as the primary navigation

#### Scenario: header.php reads menu.json on every request
- **WHEN** `includes/header.php` is loaded
- **THEN** it SHALL contain a `file_get_contents` or equivalent call reading `config/menu.json`

#### Scenario: header.php makes no DB query for nav
- **WHEN** `includes/header.php` is loaded on a page that makes no other DB calls
- **THEN** no database connection SHALL be opened solely for nav rendering

### Requirement: menu.json seeds DB on installation
A one-time migration script (or equivalent admin utility) SHALL read `config/menu.json` and INSERT its items into the `menus` DB table if the table is empty. This allows a fresh install to have a working navigation without manual DB entry.

#### Scenario: Empty menus table is seeded from JSON
- **WHEN** the migration/seed script is run against a DB where `menus` is empty
- **THEN** the `menus` table SHALL contain the items defined in `config/menu.json`, ordered by `sort_order`

#### Scenario: Non-empty menus table is not overwritten
- **WHEN** the migration/seed script is run against a DB where `menus` already has rows
- **THEN** no rows SHALL be inserted, updated, or deleted — the existing data is preserved

#### Scenario: Seeded items have correct labels and slugs
- **WHEN** `menu.json` contains `{"label":"Home","slug":"/","sort_order":1}`
- **THEN** the corresponding `menus` row SHALL have those values after seeding

### Requirement: Nav items are normalised and HTML-escaped
Each item read from `menu.json` SHALL be normalised to ensure `is_external` and `page_target` fields are present (defaulting to `0` and `""` respectively) before rendering. All label and href values rendered to HTML SHALL be passed through `h()`.

#### Scenario: Missing is_external defaults to 0
- **WHEN** a `menu.json` item lacks an `is_external` key
- **THEN** `includes/header.php` SHALL treat it as `0` (internal link) without a PHP notice

#### Scenario: Nav labels are HTML-escaped
- **WHEN** a `menu.json` label contains `<script>` characters
- **THEN** the rendered nav link text SHALL have those characters encoded via `h()`
