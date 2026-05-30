## Why

Admins cannot manage carousel slides without direct server filesystem access — there is no UI to upload images or set captions. This change adds an admin carousel page under Settings and a layout-aware public component, while preserving the existing drop-and-appear deploy-time convenience.

## What Changes

- Settings accordion gate expands from `sa`-only to `admin` + `sa`; General sub-item stays `sa`-only.
- New admin page `admin/carousel.php` (accessible to `admin` + `sa`) lists all images in `assets/img/carousel/`, allows upload, caption editing, and deletion.
- **BREAKING**: `config/slides.json` schema changes from an array of `{image_path, caption}` objects to a flat object keyed by filename: `{ "main_building.jpg": "Our Heritage Building" }`.
- New public component `public/components/carousel.php` accepts a `$layout` parameter (`full` | `col-left` | `col-right`) and renders a Bootstrap carousel. Images auto-discovered from `assets/img/carousel/`; captions overlaid from `slides.json` where present.
- Docker deployments should volume-mount `/config` and `/assets/img/carousel/` so uploaded images and caption data survive container rebuilds.

## Capabilities

### New Capabilities

- `carousel-management`: Admin UI to upload images to `assets/img/carousel/`, edit per-image captions in `config/slides.json`, and delete slides. Accessible to `admin` + `sa`.
- `carousel-component`: Public PHP component at `public/components/carousel.php`. Reads the carousel folder and `slides.json`. Renders a Bootstrap carousel. Accepts `$layout` hint (`full` | `col-left` | `col-right`) to control column wrapping for future home-page-builder placement.

### Modified Capabilities

- `carousel-folder`: BREAKING change to `slides.json` schema (array-of-objects → flat filename-keyed object). Auto-discovery model retained; `slides.json` role narrows to caption-only overlay.

## Impact

- `admin/_layout.php` — Settings accordion role gate updated.
- `admin/carousel.php` — new file.
- `config/slides.json` — schema breaking change; existing empty `[]` migrates to `{}` trivially.
- `public/components/carousel.php` — new file.
- `index.php` — includes carousel component (wired to home page as first consumer).
- Docker compose / deployment docs — add volume mounts for `/config` and `/assets/img/carousel/`.
