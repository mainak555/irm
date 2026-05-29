# Architecture Decision Records

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| [0001](0001-two-tier-config-split.md) | Two-tier config split: JSON for identity, DB for runtime | accepted | 2026-05-27 |
| [0002](0002-cfg-dot-notation-static-cache.md) | cfg() helper with dot-notation and per-request static cache | accepted | 2026-05-27 |
| [0003](0003-carousel-folder-discovery-db-overlay.md) | Carousel folder discovery with optional DB caption overlay | accepted | 2026-05-27 |
| [0004](0004-home-json-external-links-json.md) | Separate home.json and external_links.json for page-specific static copy | accepted | 2026-05-27 |
| [0005](0005-admin-writes-config-json-directly.md) | admin/settings.php writes config.json directly, dropping settings DB table | accepted | 2026-05-27 |
| [0006](0006-css-custom-properties-from-config.md) | CSS custom properties injected inline from config.json in header.php | accepted | 2026-05-27 |
| [0007](0007-replace-admin-users-with-auth-users.md) | Replace admin_users table with auth_users (new schema, roles, OIDC-ready) | accepted | 2026-05-28 |
| [0008](0008-auth-config-singleton-one-provider.md) | auth_config as a singleton — one OIDC/SAML provider at a time | accepted | 2026-05-28 |
| [0009](0009-sa-username-password-not-oidc.md) | SA super admin uses username+password, not email+OIDC | accepted | 2026-05-28 |
| [0010](0010-per-page-db-function-files.md) | Per-page DB function files instead of a monolithic db.php | accepted | 2026-05-28 |
| [0011](0011-theme-preference-in-database.md) | Admin theme preference stored in database, not localStorage | accepted | 2026-05-28 |
| [0012](0012-oidc-error-page-auth-card-layout.md) | OIDC error page uses auth-card layout for auth-flow visual continuity | accepted | 2026-05-29 |
| [0013](0013-role-rank-helper.md) | role_rank() helper for role hierarchy comparison | accepted | 2026-05-29 |
| [0014](0014-guard-target-same-rank-param.md) | guard_target() same-rank enforcement via boolean parameter | accepted | 2026-05-29 |
| [0015](0015-audit-columns-display-standard.md) | Audit columns UI standard: show Updated only; tooltip-only timezone | accepted | 2026-05-29 |
