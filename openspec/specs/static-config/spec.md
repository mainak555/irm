# static-config

## Purpose

JSON-driven site identity and branding. Root `config.php` exposes `cfg()`, `h()`, and `menu_url()` helpers backed by `config/config.json`. CSS custom properties are injected inline from config values so brand colours and the school name are editable with a text editor — no DB access or PHP change needed.

## Requirements

### Requirement: config.json schema
`config/config.json` SHALL be the single source of truth for all school identity and branding values. It SHALL contain at minimum: `school.title`, `school.subtitle`, `school.address`, `school.phone`, `school.email`, `school.social` (object with platform URLs), `colors` (object mapping CSS custom-property names to hex values), `footer.copyright`, `footer.quick_links` (array of `{label, url}` objects). No DB credentials or secrets SHALL appear in this file.

#### Scenario: File contains all required top-level sections
- **WHEN** `config/config.json` is read
- **THEN** it SHALL contain the keys `school`, `colors`, `footer`

#### Scenario: School identity fields are present
- **WHEN** `config/config.json` is decoded
- **THEN** `school.title`, `school.address`, `school.phone`, and `school.email` SHALL each be non-empty strings

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
The `setting()` PHP function (previously defined in `includes/functions.php`) SHALL be removed. All call sites that previously used `setting('key')` for branding or identity lookups SHALL be replaced with `cfg('school.<key>')` or the appropriate dot-notation path.

#### Scenario: setting() is not callable
- **WHEN** any PHP file in the project is loaded
- **THEN** the function `setting()` SHALL NOT be defined anywhere in the codebase

#### Scenario: No remaining call sites
- **WHEN** the codebase is searched for the string `setting(`
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
`admin/settings.php` SHALL POST-handle school identity changes by reading the current `config/config.json`, merging submitted form fields, and writing the updated file using `file_put_contents` with the `LOCK_EX` flag. Before overwriting, it SHALL write a backup copy to `config/config.json.bak`. After writing, it SHALL validate the new file is parseable with `json_decode`; if not, it SHALL restore the backup and show an error flash.

#### Scenario: Successful save writes file with lock
- **WHEN** the admin submits the settings form with valid data
- **THEN** `config/config.json` SHALL be updated and `config/config.json.bak` SHALL contain the previous state

#### Scenario: Corrupt write restores backup
- **WHEN** a write produces invalid JSON (simulated)
- **THEN** `config/config.json` SHALL be restored from `.bak` and the admin SHALL see an error flash

#### Scenario: CSRF token validated on POST
- **WHEN** the settings form is submitted without a valid CSRF token
- **THEN** the request SHALL be rejected with an error response before any file write occurs
