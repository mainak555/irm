# carousel-component

## Purpose

A layout-aware public PHP component at `components/carousel.php` (project root). Renders a Bootstrap carousel from images auto-discovered in `assets/img/carousel/`, with captions from `config/slides.json`. Accepts a `$layout` variable before inclusion to control Bootstrap column wrapping, enabling placement in full-width or two-column home-page layouts.

## Requirements

### Requirement: Image discovery

`components/carousel.php` SHALL discover slide images by calling `glob('assets/img/carousel/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE)` and sorting the result with `natsort()`. Only files with those extensions SHALL be included. The component SHALL NOT query any database table.

#### Scenario: Images in folder are discovered
- **WHEN** `assets/img/carousel/` contains `campus.jpg` and `hall.png`
- **THEN** the rendered carousel SHALL include both images as slides

#### Scenario: Files are sorted naturally
- **WHEN** the folder contains `slide10.jpg` and `slide2.jpg`
- **THEN** `slide2.jpg` SHALL appear before `slide10.jpg` in the carousel

#### Scenario: Non-image files are excluded
- **WHEN** `assets/img/carousel/` contains `readme.txt` alongside `campus.jpg`
- **THEN** only `campus.jpg` SHALL appear as a slide

### Requirement: Caption overlay

For each discovered image, the component SHALL look up its basename (e.g. `"campus.jpg"`) as a key in `config/slides.json`. If the key exists and its value is a non-empty string, the caption SHALL be rendered in the carousel slide. If the key is absent or the value is empty, no caption markup SHALL be rendered for that slide. All caption output SHALL be escaped with `h()`.

#### Scenario: Matching caption is rendered
- **WHEN** `slides.json` contains `{"campus.jpg": "Our Campus"}` and `campus.jpg` is in the folder
- **THEN** the slide SHALL render `"Our Campus"` as the caption text

#### Scenario: Missing caption renders no caption markup
- **WHEN** `campus.jpg` is in the folder but has no entry in `slides.json`
- **THEN** the slide SHALL render with no caption element

#### Scenario: Caption with HTML characters is escaped
- **WHEN** `slides.json` contains `{"campus.jpg": "<script>alert(1)</script>"}`
- **THEN** the rendered output SHALL display the literal characters and SHALL NOT execute the script

### Requirement: Layout parameter

The component SHALL read a `$layout` variable set by the caller before `require`. `$layout` SHALL accept three values: `'full'` (default when unset), `'col-left'`, and `'col-right'`. The carousel wrapper SHALL apply the appropriate Bootstrap column class based on the value.

#### Scenario: No layout variable defaults to full width
- **WHEN** `components/carousel.php` is included without setting `$layout`
- **THEN** the carousel wrapper SHALL carry the class `col-12`

#### Scenario: Layout full renders full-width column
- **WHEN** `$layout` is set to `'full'` before including the component
- **THEN** the carousel wrapper SHALL carry the class `col-12`

#### Scenario: Layout col-left renders left half-column
- **WHEN** `$layout` is set to `'col-left'` before including the component
- **THEN** the carousel wrapper SHALL carry the class `col-md-6` and be positioned as the left column

#### Scenario: Layout col-right renders right half-column
- **WHEN** `$layout` is set to `'col-right'` before including the component
- **THEN** the carousel wrapper SHALL carry the class `col-md-6` and be positioned as the right column

### Requirement: Empty folder renders nothing

If no image files are found in `assets/img/carousel/`, the component SHALL produce no HTML output. It SHALL NOT render an empty carousel container or any placeholder markup.

#### Scenario: Empty folder produces no output
- **WHEN** `assets/img/carousel/` contains no image files
- **THEN** the component SHALL produce no HTML output

#### Scenario: Folder with only .gitkeep produces no output
- **WHEN** `assets/img/carousel/` contains only `.gitkeep`
- **THEN** the component SHALL produce no HTML output
