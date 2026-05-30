## MODIFIED Requirements

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

---

### Requirement: setting() helper removal
The `setting()` PHP function SHALL be removed. All call sites that previously used `setting('key')` for branding or identity lookups SHALL be replaced with `cfg('general.<key>')` or the appropriate dot-notation path. No call site SHALL use `cfg('school.*')` — all references to the old key SHALL be updated to `cfg('general.*')`.

#### Scenario: setting() is not callable
- **WHEN** any PHP file in the project is loaded
- **THEN** the function `setting()` SHALL NOT be defined anywhere in the codebase

#### Scenario: No remaining call sites for setting()
- **WHEN** the codebase is searched for the string `setting(`
- **THEN** zero matches SHALL be found in `*.php` files

#### Scenario: No remaining cfg('school.*') call sites
- **WHEN** the codebase is searched for the string `cfg('school.`
- **THEN** zero matches SHALL be found in `*.php` files

---

### Requirement: admin writes config.json with file lock
`admin/config_general.php` (replaces `admin/settings.php`) SHALL POST-handle school identity and theme pack changes by reading the current `config/config.json`, merging submitted form fields into only the `general` and `public` sub-arrays, and writing the updated file using `file_put_contents` with the `LOCK_EX` flag. The `colors` and `footer` sections SHALL remain untouched. Before overwriting, it SHALL write a backup copy to `config/config.json.bak`. After writing, it SHALL validate the new file is parseable with `json_decode`; if not, it SHALL restore the backup and show an error flash. Access is restricted to `sa` role only.

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
