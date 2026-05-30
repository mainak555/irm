# carousel-folder (delta)

## REMOVED Requirements

### Requirement: Caption overlay from slides.json

**Reason**: The previous array-of-objects schema (`[{image_path, caption}]`) is replaced by a flat filename-keyed object (`{"filename.jpg": "caption"}`). The old format conflated image path resolution with caption lookup; the new format is keyed by basename only, which is simpler and consistent with the admin management UI.

**Migration**: Replace the contents of `config/slides.json` with a flat object. Example:

```json
// Before
[{"image_path": "assets/img/carousel/campus.jpg", "caption": "Our Campus"}]

// After
{"campus.jpg": "Our Campus"}
```

Any reader of `slides.json` must be updated to use `$captions[basename($image)]` instead of iterating the array.

### Requirement: slides.json-only slides still rendered

**Reason**: The union-merge model (folder images + JSON-only entries) is removed. `slides.json` is now a caption-only overlay. All visible slides must physically exist in `assets/img/carousel/`. JSON-only slides (entries pointing to paths outside the folder) are no longer supported, as the admin upload UI is the intended authoring path for new slides.

**Migration**: Any slides defined only in `slides.json` (with no corresponding file in `assets/img/carousel/`) must have their image file placed in the carousel folder or they will no longer appear.

## ADDED Requirements

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
