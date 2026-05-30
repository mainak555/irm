# carousel-folder

## Purpose

Hero carousel with zero-friction image management. Drop a file into `assets/img/carousel/` and it appears as a slide on next page load — no DB entry or code change required. `config/slides.json` provides optional caption overrides as a flat `{filename: caption}` object (replaces the `hero_slides` DB table overlay). JSON-only slides are not supported; every visible slide must have a physical file in the folder.

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

### Requirement: Caption overlay from slides.json flat object

`index.php` (and the carousel component) SHALL read `config/slides.json` as a flat JSON object where each key is an image basename (e.g. `"campus.jpg"`) and each value is a caption string. For each glob-discovered image, the reader SHALL look up `basename($imagePath)` in the decoded object. If the key exists and its value is a non-empty string, that string SHALL be used as the slide caption. If the key is absent or the value is empty, no caption SHALL be rendered for that slide.

#### Scenario: Flat object key matches basename
- **WHEN** `slides.json` is `{"campus.jpg": "Our Campus"}` and `assets/img/carousel/campus.jpg` exists
- **THEN** the carousel SHALL render that slide with the caption `"Our Campus"`

#### Scenario: Missing key renders no caption
- **WHEN** `hall.png` is in the folder but `slides.json` has no `"hall.png"` key
- **THEN** the carousel SHALL render that slide with no caption element

#### Scenario: Empty value renders no caption
- **WHEN** `slides.json` contains `{"campus.jpg": ""}` and `campus.jpg` is in the folder
- **THEN** the carousel SHALL render that slide with no caption element

#### Scenario: slides.json decoded as object not array
- **WHEN** `slides.json` is read via `json_decode(file_get_contents(...), true)`
- **THEN** the result SHALL be a PHP associative array (not a list), with string keys and string values

### Requirement: Carousel folder must exist
The `assets/img/carousel/` directory SHALL exist in the repository (tracked via a `.gitkeep` file) so that `glob()` does not produce a warning on a fresh checkout.

#### Scenario: Folder present on fresh checkout
- **WHEN** the repository is checked out fresh
- **THEN** `assets/img/carousel/` SHALL exist as a directory (containing at minimum a `.gitkeep`)

#### Scenario: glob() produces no PHP warning on empty folder
- **WHEN** `assets/img/carousel/` exists but contains no image files
- **THEN** `glob(...)` SHALL return an empty array with no PHP warning
