## Why

School identity and branding live in `config/config.json` but there is no admin UI to edit them — a non-developer `sa` user must SSH into the server and hand-edit JSON. This change adds a **Settings → General** page to the admin panel so school identity fields and the active public theme pack can be managed through the browser.

## What Changes

- **BREAKING**: Rename `config.json → school` key to `general`. All `cfg('school.*')` call sites (12 calls across 4 files) updated to `cfg('general.*')`.
- Add `config.json → public.theme` field: slug of the active CSS theme pack (e.g. `"classic"`).
- Add a **Settings** top-level accordion to the admin sidebar, visible to `sa` role only.
- Rename the existing "Settings" sub-item under Authorization to **"Configurations"** (it links to `auth_config.php` — OIDC provider, not site identity).
- New page `admin/config_general.php`: form to edit `general.*` fields and `public.theme`; `sa` role only; writes `config/config.json` via `file_put_contents` + `LOCK_EX` + backup-before-write.
- Scaffold `public/css/themes/` directory with at least one starter CSS file (`classic.css`). Available packs discovered by filesystem scan — no manifest file.
- Extend `static-config` spec: update filename reference (`admin/settings.php` → `admin/config_general.php`), add Settings accordion requirement to `admin-shell` spec.

## Capabilities

### New Capabilities

- `public-theme-pack`: Filesystem-discovered CSS theme packs under `public/css/themes/`. Active pack slug stored in `config.json → public.theme`, read via `cfg('public.theme')`. Public view loads the active pack's CSS file. Admin General page provides a dropdown to select among discovered packs.

### Modified Capabilities

- `static-config`: config.json schema gains `general` key (replaces `school`) and `public.theme` key. Admin write handler is `admin/config_general.php` (corrects placeholder `admin/settings.php` from ADR-0005). General form scope: `general.*` fields + `public.theme` only — `colors` and `footer` sections are not edited here.
- `admin-shell`: Sidebar gains a **Settings** top-level accordion (sa only) with a **General** sub-item. Existing "Settings" sub-item under Authorization renamed to **"Configurations"**.

## Impact

- **`config/config.json`**: Schema change — `school` → `general`, new `public` section added.
- **`includes/header.php`**, **`includes/footer.php`**, **`admin/login.php`**, **`admin/_layout.php`**: All `cfg('school.*')` calls updated to `cfg('general.*')` (12 call sites).
- **`admin/_layout.php`**: Settings accordion added; Authorization "Settings" link renamed to "Configurations".
- **New file**: `admin/config_general.php`.
- **New directory**: `public/css/themes/` with `classic.css` starter file.
- **`openspec/specs/static-config/spec.md`**: Updated to reflect new key names and correct write-handler filename.
- **`openspec/specs/admin-shell/spec.md`**: Updated to reflect renamed Configurations link and new Settings accordion.
- No DB schema changes. No new tables.
