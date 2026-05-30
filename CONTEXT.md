# IRM Admin

The admin panel for a self-hosted school CMS. Manages users, content, and authentication configuration.

## Language

### Admin Navigation

**Configurations**:
The sub-item under the Authorization accordion linking to `admin/auth_config.php`. Manages the OIDC/SAML authentication provider. Distinguished from General (site identity) by scope: Configurations = who can log in; General = what the site looks like.
_Avoid_: Settings (ambiguous — Settings is a top-level accordion), Auth Settings

**Settings** (accordion):
The top-level sidebar accordion visible only to `sa` role. Contains sub-items for site-wide configuration stored in JSON files: General, and future items (Home, Menu). Distinct from Authorization which manages users and auth providers.
_Avoid_: Config menu, Admin settings

**General**:
The sub-item under the Settings accordion linking to `admin/config_general.php`. Edits `config/config.json → general.*` fields (title, subtitle, logoUrl, address, phone, fax, email, social.facebook) and the active theme pack slug (`public.theme`). The `colors` and `footer` sections of config.json are out of scope for this page.
_Avoid_: Basic Configuration, Site Settings, Settings

**`general` (config key)**:
The top-level key in `config/config.json` that holds school identity and contact fields. Replaces the previous `school` key — all `cfg('school.*')` calls become `cfg('general.*')`. This rename is a **BREAKING** change tracked in the json-config-settings proposal.
_Avoid_: `school` key (deprecated)

**Theme pack**:
A named CSS file under `public/css/themes/` (e.g., `classic.css`, `modern.css`) that controls the public view's visual identity. The active pack slug is stored in `config.json → public.theme` and read via `cfg('public.theme')`. Available packs are discovered by scanning `public/css/themes/*.css` at request time — no manifest file. Distinct from the admin theme (light/dark/system) stored per-user in `auth_users.theme`.
_Avoid_: template, skin, public theme (use "theme pack" as the noun phrase)

### User Management

**Sentinel**:
The one bootstrap `sa` account identified by `email = 'admin' AND role = 'sa'`. It is created at setup, cannot be modified or deleted by anyone, and bypasses all permission checks.
_Avoid_: super admin, god user, root user

**Creator**:
The authenticated user whose `id` is stored in `created_by` at the time a row is inserted. Ownership is immutable — role changes do not transfer it.
_Avoid_: owner, author

**Creator-scoped**:
A permission model where full CRUD on a same-rank peer is granted only when the acting user is the peer's creator. Cross-rank actors (higher rank → lower rank) are never restricted by creator-scoping.
_Avoid_: owner-based, creator-owned

**Peer row**:
A same-rank user row that the logged-in user did not create. Rendered with `irm-peer-row` styling: active toggle remains operable, all other controls are disabled and the action menu is absent.
_Avoid_: locked row, read-only row

**Role rank**:
A numeric ordering of roles used for hierarchy checks: `sa`=3, `admin`=2, `faculty`=1, `user`=0. A higher-rank actor may freely act on any lower-rank target regardless of creator-scoping.
_Avoid_: role level, role weight

**Re-assign on delete**:
Before a user row is deleted, all rows whose `created_by` equals the deleted user's `id` are updated to `created_by = deleter_id`. Prevents orphaned rows.
_Avoid_: ownership transfer, cascade reassign
