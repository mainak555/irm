# carousel-folder

## Purpose

Hero carousel with zero-friction image management. Drop a file into `assets/img/carousel/` and it appears as a slide on next page load — no DB entry or code change required. `config/slides.json` provides optional caption overrides and JSON-only slides (replaces the `hero_slides` DB table overlay).

## Requirements

### Requirement: Images auto-discovered from carousel folder
`index.php` SHALL use `glob('assets/img/carousel/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE)` to discover slide images. The resulting array SHALL be sorted with `natsort()` to produce a consistent, OS-independent order. Any image file placed in `assets/img/carousel/` SHALL appear as a carousel slide on the next page load without any code change or DB entry.

#### Scenario: New image appears automatically
- **WHEN** a `.jpg` file is copied into `assets/img/carousel/`
- **THEN** on the next page load the carousel SHALL include a slide using that image

#### Scenario: Supported file extensions are discovered
- **WHEN** `assets/img/carousel/` contains files with extensions `.jpg`, `.jpeg`, `.png`, `.gif`, and `.webp`
- **THEN** all five files SHALL be included in the slide list

#### Scenario: Files are sorted naturally
- **WHEN** the folder contains `slide10.jpg` and `slide2.jpg`
- **THEN** `natsort()` SHALL order them as `slide2.jpg` before `slide10.jpg`

#### Scenario: Unsupported extensions are ignored
- **WHEN** `assets/img/carousel/` contains a `readme.txt` and a `slide.bmp`
- **THEN** neither file SHALL appear in the carousel slide list

### Requirement: Caption overlay from slides.json
If a glob-discovered image has no matching entry in `config/slides.json` (matched by basename), `index.php` SHALL use the image filename without its extension as the slide caption, with underscores replaced by spaces and the string title-cased. If a matching entry exists in `slides.json` its `caption` field SHALL be used instead.

`config/slides.json` SHALL be a JSON array of objects with at minimum `image_path` (string) and `caption` (string). This file replaces the DB `hero_slides` table for caption overlay and JSON-only slides.

#### Scenario: No slides.json entry uses filename as caption
- **WHEN** `assets/img/carousel/main_building.jpg` exists but no entry in `slides.json` has `image_path` matching that filename
- **THEN** the slide SHALL render with the caption `"Main Building"`

#### Scenario: slides.json entry caption takes precedence
- **WHEN** `slides.json` contains an entry with `image_path` matching `main_building.jpg` and `caption = "Our Heritage Building"`
- **THEN** the slide SHALL render with `"Our Heritage Building"` as the caption

#### Scenario: Caption text is HTML-escaped
- **WHEN** a `slides.json` caption contains `<script>alert(1)</script>`
- **THEN** the rendered carousel caption SHALL display the literal characters and not execute the script

### Requirement: slides.json-only slides still rendered
Slide entries in `config/slides.json` with an `image_path` that does not match any file in `assets/img/carousel/` (e.g., paths pointing elsewhere) SHALL still be rendered by `index.php`. The final slide list SHALL be the union of folder-discovered images and JSON-only slides, deduplicated by basename.

#### Scenario: slides.json entry with non-folder path renders
- **WHEN** a `slides.json` entry has `image_path = "assets/img/special/banner.jpg"` not present in the carousel folder
- **THEN** the carousel SHALL include a slide with that image source

#### Scenario: Folder image matched to slides.json entry not duplicated
- **WHEN** a `slides.json` entry has `image_path` matching the basename of a file in `assets/img/carousel/`
- **THEN** that image SHALL appear exactly once in the slide list

#### Scenario: Empty folder falls back to slides.json-only entries
- **WHEN** `assets/img/carousel/` is empty but `slides.json` has entries
- **THEN** the carousel SHALL still render the JSON slides

### Requirement: Carousel folder must exist
The `assets/img/carousel/` directory SHALL exist in the repository (tracked via a `.gitkeep` file) so that `glob()` does not produce a warning on a fresh checkout.

#### Scenario: Folder present on fresh checkout
- **WHEN** the repository is checked out fresh
- **THEN** `assets/img/carousel/` SHALL exist as a directory (containing at minimum a `.gitkeep`)

#### Scenario: glob() produces no PHP warning on empty folder
- **WHEN** `assets/img/carousel/` exists but contains no image files
- **THEN** `glob(...)` SHALL return an empty array with no PHP warning
