# static-config

## Purpose

JSON-driven site identity and branding. Root `config.php` exposes `cfg()`, `h()`, and `menu_url()` helpers backed by `config/config.json`. CSS custom properties are injected inline from config values so brand colours and the school name are editable with a text editor — no DB access or PHP change needed.

## Requirements

### Requirement: config.json schema
`config/config.json` SHALL be the single source of truth for all school identity and branding values. It SHALL contain at minimum: `general.title`, `general.subtitle`, `general.address`, `general.phone`, `general.email`, `general.social` (object with platform URLs), `colors` (object mapping CSS custom-property names to hex values), `footer.copyright`, `footer.quick_links` (array of `{label, url}` objects), and `public.theme` (string — active CSS theme pack slug). The previous top-level key `school` is replaced by `general`. No DB credentials or secrets SHALL appear in this file.

#### Scenario: File contains all required top-level sections
- **WHEN** `config/config.json` is read
- **THEN** it SHALL contain the top-level keys `general`, `colors`, `footer`, and `public`

#### Scenario: general key present and school key absent
- **WHEN** `config/config.json` is decoded
- **THEN** it SHALL contain the top-level key `general`
- **AND** it SHALL NOT contain the top-level key `school`

#### Scenario: General identity fields are present
- **WHEN** `config/config.json` is decoded
- **THEN** `general.title`, `general.address`, `general.phone`, and `general.email` SHALL each be non-empty strings

#### Scenario: public.theme key is a non-empty string
- **WHEN** `config/config.json` is decoded
- **THEN** `public.theme` SHALL be a non-empty string

#### Scenario: No secrets in file
- **WHEN** `config/config.json` is inspected
- **THEN** it SHALL NOT contain keys named `password`, `db_pass`, `secret`, `key`, or any DB credentials

### Requirement: cfg(), h(), and menu_url() helper functions
Root `config.php` SHALL expose three helpers: `cfg(string $key, mixed $default = null): mixed` (dot-notation access into `config/config.json`), `h(?string $s): string` (HTML escaping via `htmlspecialchars`), and `menu_url(array $m): string` (builds a href from a nav item). The parsed `config.json` array SHALL be cached in a `static` variable so the file is read at most once per PHP request. No separate `includes/config.php` SHALL exist.

#### Scenario: Dot-notation access to nested value
- **WHEN** `cfg('school.title')` is called
- **THEN** it SHALL return the string value at `config.json → school → title`

#### Scenario: Missing key returns default
- **WHEN** `cfg('nonexistent.key', 'fallback')` is called
- **THEN** it SHALL return `'fallback'` without a warning or error

#### Scenario: config.json is read at most once per request
- **WHEN** `cfg()` is called multiple times in the same request
- **THEN** `file_get_contents` (or equivalent) SHALL be invoked exactly once; subsequent calls use the static cache

#### Scenario: Top-level key access
- **WHEN** `cfg('colors')` is called
- **THEN** it SHALL return the full `colors` object as a PHP array

### Requirement: setting() helper removal
The `setting()` PHP function (previously defined in `includes/functions.php`) SHALL be removed. All call sites that previously used `setting('key')` for branding or identity lookups SHALL be replaced with `cfg('general.<key>')` or the appropriate dot-notation path. No call site SHALL use `cfg('school.*')` — all references to the old key SHALL be updated to `cfg('general.*')`.

#### Scenario: setting() is not callable
- **WHEN** any PHP file in the project is loaded
- **THEN** the function `setting()` SHALL NOT be defined anywhere in the codebase

#### Scenario: No remaining call sites for setting()
- **WHEN** the codebase is searched for the string `setting(`
- **THEN** zero matches SHALL be found in `*.php` files

#### Scenario: No remaining cfg('school.*') call sites
- **WHEN** the codebase is searched for the string `cfg('school.`
- **THEN** zero matches SHALL be found in `*.php` files

### Requirement: settings DB table removal
The `settings` database table SHALL be dropped. No PHP code SHALL query `SELECT * FROM settings` or `INSERT INTO settings`. The `quick_links` database table SHALL also be dropped; its data moves to `config.json → footer.quick_links`.

#### Scenario: settings table absent from schema
- **WHEN** `sql/schema.sql` is inspected
- **THEN** it SHALL NOT contain `CREATE TABLE settings`

#### Scenario: quick_links table absent from schema
- **WHEN** `sql/schema.sql` is inspected
- **THEN** it SHALL NOT contain `CREATE TABLE quick_links`

#### Scenario: No DB queries reference removed tables
- **WHEN** all PHP files are searched for `FROM settings` or `FROM quick_links`
- **THEN** zero matches SHALL be found

### Requirement: CSS custom properties injected from config
`includes/header.php` SHALL read `cfg('colors')` and emit an inline `<style>:root { … }</style>` block inside `<head>`, immediately after the `<link>` to `site.css`. Each key-value pair in the `colors` object SHALL become a CSS custom property declaration (`--<key>: <value>;`). All values SHALL be passed through `h()` before being written to the HTML.

#### Scenario: Inline style block is emitted
- **WHEN** `includes/header.php` is rendered
- **THEN** the HTML response SHALL contain `<style>:root {` inside the `<head>` element

#### Scenario: Color values are escaped
- **WHEN** a color value in config.json contains `</style>` or similar injection attempt
- **THEN** `h()` SHALL encode it and the closing tag SHALL NOT be broken

#### Scenario: Custom property names match config keys
- **WHEN** `config.json → colors` contains `{ "cream": "#F5F0E8", "link": "#1A4A8A" }`
- **THEN** the emitted block SHALL contain `--cream: #F5F0E8;` and `--link: #1A4A8A;`

### Requirement: admin writes config.json with file lock
`admin/config_general.php` SHALL POST-handle school identity and theme pack changes by reading the current `config/config.json`, merging submitted form fields into only the `general` and `public` sub-arrays, and writing the updated file using `file_put_contents` with the `LOCK_EX` flag. The `colors` and `footer` sections SHALL remain untouched. Before overwriting, it SHALL write a backup copy to `config/config.json.bak`. After writing, it SHALL validate the new file is parseable with `json_decode`; if not, it SHALL restore the backup and show an error flash. Access is restricted to `sa` role only.

#### Scenario: Only general and public sections updated on save
- **GIVEN** an `sa` admin is on the General settings page
- **AND** `config.json` contains existing `colors` and `footer` sections
- **WHEN** the admin submits the form with updated school identity and theme pack values
- **THEN** `config.json → general.*` fields SHALL reflect the submitted values
- **AND** `config.json → public.theme` SHALL reflect the submitted pack slug
- **AND** `config.json → colors` SHALL be unchanged
- **AND** `config.json → footer` SHALL be unchanged

#### Scenario: Successful save writes file with lock and backup
- **GIVEN** an `sa` admin submits the General form with valid data
- **WHEN** the form is processed
- **THEN** `config/config.json` SHALL be updated
- **AND** `config/config.json.bak` SHALL contain the previous file state

#### Scenario: Corrupt write restores backup
- **WHEN** a write produces invalid JSON (simulated)
- **THEN** `config/config.json` SHALL be restored from `.bak`
- **AND** the admin SHALL see an error flash

#### Scenario: Non-sa role is denied access
- **GIVEN** a user with role `admin` is authenticated
- **WHEN** they request the General settings page
- **THEN** they SHALL receive an HTTP 403 response
- **AND** no changes SHALL be made to `config.json`

#### Scenario: CSRF token validated on POST
- **WHEN** the General form is submitted without a valid CSRF token
- **THEN** the request SHALL be rejected with an error response before any file write occurs
