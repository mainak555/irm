# public-theme-pack

## Purpose

CSS theme pack system for the public-facing site. Theme packs are plain CSS files under `assets/css/themes/`. The active pack slug is stored in `config.json → public.theme` and selected via the admin General settings page. No manifest file is used — adding a pack requires only dropping a `.css` file into the directory.

## Requirements

### Requirement: Theme pack CSS files discoverable by filesystem scan
The system SHALL discover available theme packs at request time by scanning `assets/css/themes/*.css` using `glob()`. Each file's stem (filename without `.css`) SHALL be used as the pack slug. Labels SHALL be derived by replacing hyphens with spaces and title-casing the result (e.g. `warm-earth` → "Warm Earth"). No manifest file is used; adding a pack requires only dropping a `.css` file into the directory.

#### Scenario: Packs appear in dropdown from filesystem
- **GIVEN** `assets/css/themes/` contains `classic.css` and `modern.css`
- **WHEN** an `sa` admin opens the General settings page
- **THEN** the theme pack dropdown SHALL list "Classic" as one option
- **AND** the theme pack dropdown SHALL list "Modern" as one option

#### Scenario: Active pack is pre-selected
- **GIVEN** `assets/css/themes/warm-earth.css` exists
- **AND** the active theme pack is configured as `"warm-earth"`
- **WHEN** an `sa` admin opens the General settings page
- **THEN** "Warm Earth" SHALL be the selected option in the theme pack dropdown

#### Scenario: Empty themes directory shows warning
- **GIVEN** `assets/css/themes/` exists but contains no `.css` files
- **WHEN** an `sa` admin opens the General settings page
- **THEN** the theme pack dropdown SHALL be empty
- **AND** the page SHALL display a warning that no theme packs are available

---

### Requirement: Active theme pack loaded on public view
Public pages SHALL load the active theme pack CSS file by reading `cfg('public.theme')` and emitting a `<link rel="stylesheet">` pointing to `assets/css/themes/{slug}.css`. The value SHALL be passed through `h()` before output. If the `public.theme` key is absent or the corresponding file does not exist on disk, the public view SHALL fall back to `classic`.

#### Scenario: Active pack stylesheet linked in public page
- **GIVEN** `assets/css/themes/modern.css` exists
- **AND** the active theme pack is configured as `"modern"`
- **WHEN** a visitor loads any public page
- **THEN** the page response SHALL include a stylesheet link to `/assets/css/themes/modern.css`

#### Scenario: Missing pack file falls back to classic
- **GIVEN** the active theme pack is configured as `"nonexistent"`
- **AND** `assets/css/themes/nonexistent.css` does not exist
- **WHEN** a visitor loads any public page
- **THEN** the page response SHALL include a stylesheet link to `/assets/css/themes/classic.css`

#### Scenario: Absent public.theme key falls back to classic
- **GIVEN** `config.json` does not contain a `public.theme` key
- **WHEN** a visitor loads any public page
- **THEN** the page response SHALL include a stylesheet link to `/assets/css/themes/classic.css`
