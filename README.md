# IRM — School / Institute Admin CMS

A lightweight, self-hosted **PHP + MySQL** CMS with an admin panel for schools
and training institutes. Zero Composer, zero npm, zero build step. Deploy by
editing one JSON file.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Quick Start — local PHP server](#2-quick-start--local-php-server)
3. [Environment Variables](#3-environment-variables)
4. [Docker](#4-docker)
5. [Docker Compose — full stack](#5-docker-compose--full-stack)
6. [Production Deployment (Apache / Nginx)](#6-production-deployment-apache--nginx)
7. [First Launch — create the admin account](#7-first-launch--create-the-admin-account)
8. [Admin Panel Overview](#8-admin-panel-overview)
9. [Roles & Permissions](#9-roles--permissions)
10. [Theme — Dark / Light / System](#10-theme--dark--light--system)
11. [Google OIDC Setup](#11-google-oidc-setup)
12. [Other OIDC Providers](#12-other-oidc-providers)
13. [Branding — config.json](#13-branding--configjson)
14. [Folder Layout](#14-folder-layout)
15. [Database Schema](#15-database-schema)
16. [Audit Columns — created\_at / updated\_at / created\_by / updated\_by](#16-audit-columns--created_at--updated_at--created_by--updated_by)
17. [Security Notes](#17-security-notes)
18. [PHP Configuration](#18-php-configuration)
19. [Troubleshooting](#19-troubleshooting)

---

## 1. Prerequisites

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 8.0 | 8.2+ |
| PHP extensions | `openssl`, `pdo_mysql` | `openssl`, `pdo_mysql`, `pdo_odbc`, `mbstring` |
| Database | MySQL 5.7 / MariaDB 10.3 | MySQL 8+ / MariaDB 10.6+ |
| Web server | PHP built-in (`php -S`) | Apache 2.4 / Nginx |
| Docker (optional) | 20.x | latest |

> No Composer, no npm, no build step required.

---

## 2. Quick Start — local PHP server

```bash
# 1. Clone
git clone <repo-url> irm
cd irm

# 2. Copy and edit environment file
cp env.example .env
# Open .env and fill in DB_HOST, DB_NAME, DB_USER, DB_PASS

# 3. Create the database
mysql -u root -p -e "CREATE DATABASE irm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Import the schema
mysql -u root -p irm < sql/schema.sql

# 5. Start the built-in PHP server
php -S 0.0.0.0:8080

# 6. Open in browser
#   Public site:  http://localhost:8080/
#   Admin panel:  http://localhost:8080/admin/
```

On first visit to `/admin/` you will be prompted to create the super-admin
password (see [§7](#7-first-launch--create-the-admin-account)).

---

## 3. Environment Variables

Copy `env.example` → `.env` and set these values. **Never commit `.env` to
version control.**

| Variable | Required | Default | Description |
|---|---|---|---|
| `DB_HOST` | Yes | `127.0.0.1` | MySQL hostname |
| `DB_PORT` | No | `3306` | MySQL port |
| `DB_NAME` | Yes | `irm` | Database name |
| `DB_USER` | Yes | — | Database username |
| `DB_PASS` | Yes | — | Database password |
| `APP_SECRET` | Yes | — | Random string used for session security. Generate with `php -r "echo bin2hex(random_bytes(32));"` |
| `APP_DEBUG` | No | `false` | Set `true` only on local dev — shows PHP errors in the browser |
| `UPLOAD_MAX_BYTES` | No | `5242880` | Maximum file upload size in bytes. Single source of truth for all upload features — changing this here automatically updates both server-side validation and the client-side drop zone limit. Default is 5 MB. |

### Generating APP_SECRET

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Paste the output as the value of `APP_SECRET` in `.env`.

### .env example

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=irm
DB_USER=cms_user
DB_PASS=a-strong-password

APP_SECRET=c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2
APP_DEBUG=false

# Maximum file upload size in bytes — single source of truth for all uploads (default: 5 MB)
# UPLOAD_MAX_BYTES=5242880
```

---

## 4. Docker

### Build

```bash
docker build -t irm-cms .
```

### Prepare `.env`

```bash
cp env.example .env
# Edit .env — set all DB_* vars and APP_SECRET
# For a DB server on your host machine use:
#   DB_HOST=host.docker.internal   (Mac / Windows Docker Desktop)
#   DB_HOST=172.17.0.1             (Linux)
```

### Seed the database (once)

Run against your existing MySQL instance before starting the container:

```bash
mysql -h <DB_HOST> -u <DB_USER> -p <DB_NAME> < sql/schema.sql
```

### Run

```bash
docker run --env-file .env -p 8080:80 irm-cms
```

| URL | |
|---|---|
| `http://localhost:8080/` | Public site |
| `http://localhost:8080/admin/` | Admin panel |

---

## 5. Docker Compose — full stack

Create `docker-compose.yml` alongside the `Dockerfile`:

```yaml
services:
  db:
    image: mysql:8
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: irm
      MYSQL_USER: cms
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: rootsecret
    volumes:
      - db-data:/var/lib/mysql
      - ./sql/schema.sql:/docker-entrypoint-initdb.d/schema.sql

  app:
    build: .
    restart: unless-stopped
    ports:
      - "8080:80"
    env_file: .env
    depends_on:
      - db
    volumes:
      - ./config:/var/www/html/config
      - ./assets/img/carousel:/var/www/html/assets/img/carousel
      # - ./assets/img/gallery:/var/www/html/assets/img/gallery  # add when gallery ships

volumes:
  db-data:
```

Set `DB_HOST=db` in `.env` (matches the service name), then:

```bash
docker compose up -d
```

The schema is auto-imported on first DB container start via
`docker-entrypoint-initdb.d`.

---

## 6. Production Deployment (Apache / Nginx)

### Apache (mod_php or PHP-FPM)

Drop the project into the document root (e.g. `/var/www/html/irm`) and ensure
`AllowOverride All` is set for that directory. No `.htaccess` rewrites are
required — all URLs are explicit PHP files.

```apache
<VirtualHost *:443>
    ServerName irm.yourschool.org
    DocumentRoot /var/www/html/irm

    <Directory /var/www/html/irm>
        AllowOverride All
        Require all granted
    </Directory>

    # TLS — use certbot / Let's Encrypt
    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/irm.yourschool.org/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/irm.yourschool.org/privkey.pem
</VirtualHost>
```

### Nginx + PHP-FPM

```nginx
server {
    listen 443 ssl;
    server_name irm.yourschool.org;

    root /var/www/html/irm;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/irm.yourschool.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/irm.yourschool.org/privkey.pem;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass  unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Block direct access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }
}
```

### Production checklist

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set a strong, random `APP_SECRET`
- [ ] Serve over HTTPS
- [ ] Ensure `.env` is not web-accessible (Apache: place outside `DocumentRoot`,
  or deny in config)
- [ ] Set file permissions: `chmod 640 .env`
- [ ] Change the default admin password immediately after first login
- [ ] If using OIDC, add the production `redirect_uri` to your OAuth app

---

## 7. First Launch — create the admin account

On a fresh database the `auth_users` table is empty. When you first open
`/admin/` (or `/admin/login.php`), the setup screen appears instead of the
normal login form.

### Setup screen fields

| Field | Value |
|---|---|
| **Login name** | Pre-filled as `admin` — this is the super-admin (`sa`) account. Use `admin` to log in. Cannot be changed. |
| **Password** | Must be ≥ 8 characters with at least one uppercase letter, one number, and one special character. |
| **Confirm Password** | Must match. |

Click **Create Admin Account**. You are then redirected to the login page.

> After first login, go to **Profile** → **Change Password** if you want to
> update it, and set your preferred **Theme**.

---

## 8. Admin Panel Overview

Navigate to `/admin/` and sign in. The sidebar groups all admin screens.

| Screen | Path | Access | Description |
|---|---|---|---|
| **Dashboard** | `/admin/` | All roles | Welcome screen + logged-in user info |
| **Profile** | `/admin/profile.php` | All roles | Change password, set display theme |
| **Users** | `/admin/users.php` | `sa`, `admin` | Add, edit, deactivate, and delete admin users |
| **Auth Config** | `/admin/auth_config.php` | `sa` only | Configure OIDC / SAML SSO provider |
| **Logout** | `/admin/logout.php` | All roles | Destroy session |

### Navbar

The top navbar shows:
- **Site title** (from `config/config.json`)
- **Logged-in user name + role badge**
- **Theme switcher** — Light / Dark / System (saves immediately, returns to the current page)
- **Logout** button

### Sidebar

The left sidebar is collapsible. Click the **`‹`** chevron on the sidebar's right edge to collapse it — the work area expands to full width. Click the **`›`** tab at the screen's left edge to pin it back open. While collapsed, hovering near the left edge reveals the sidebar as a floating overlay without pinning it. State is saved in `localStorage` and restored on every page load.

---

## 9. Roles & Permissions

| Role | Badge | Can access |
|---|---|---|
| `sa` | Super Admin | Everything — all pages including Auth Config |
| `admin` | Admin | Dashboard, Profile, Users |
| `faculty` | Faculty | Dashboard, Profile |
| `user` | User | Dashboard, Profile |

Roles are enforced server-side via `require_auth('sa', 'admin')` at the top of
each restricted page. Unauthorized access returns a themed `403` page.

The first account created at setup always gets the `sa` role. Additional users
are created and managed via the **Users** screen (`sa` and `admin` roles).

---

## 10. Theme — Dark / Light / System

Every user has a personal theme preference stored in the database.

| Setting | Behavior |
|---|---|
| **Light** | Forces the Material Shadcn light palette |
| **Dark** | Forces the Material Shadcn dark palette |
| **System** | Follows the OS `prefers-color-scheme` media query — updates automatically if the OS switches |

### Changing theme

**Option A — Navbar** (any page): Use the select dropdown in the top-right of
the navbar. Change takes effect immediately (form auto-submits).

**Option B — Profile page**: Go to `Profile` → `Theme` section → select and
click **Save Theme**.

The login and 403 pages always follow the OS preference (no user session
available).

---

## 11. Google OIDC Setup

This guide walks through connecting your IRM admin panel to **Google Sign-In**
using OpenID Connect (OIDC).

> **Prerequisite:** Your site must be accessible over **HTTPS** in production.
> For local testing, `http://localhost` is allowed by Google.

### Step 1 — Create a Google Cloud project

1. Open [Google Cloud Console](https://console.cloud.google.com/).
2. Click the project dropdown (top left) → **New Project**.
3. Give it a name (e.g. `IRM School CMS`) and click **Create**.
4. Select the new project.

### Step 2 — Configure the OAuth consent screen

1. In the left sidebar go to **APIs & Services** → **OAuth consent screen**.
2. Choose **External** (any Google account can log in) or **Internal**
   (Google Workspace accounts in your organisation only).
3. Fill in the required fields:
   - **App name**: `IRM Admin` (or your school name)
   - **User support email**: your email
   - **Developer contact email**: your email
4. Click **Save and Continue**.
5. On the **Scopes** step add:
   - `openid`
   - `email`
   - `profile`
6. Click **Save and Continue** through the remaining steps.

### Step 3 — Create OAuth credentials

1. Go to **APIs & Services** → **Credentials**.
2. Click **+ Create Credentials** → **OAuth client ID**.
3. **Application type**: `Web application`.
4. **Name**: `IRM Admin Login` (any name).
5. Under **Authorized redirect URIs** click **+ Add URI** and enter:
   ```
   https://yourdomain.com/admin/auth/callback.php
   ```
   For local development also add:
   ```
   http://localhost:8080/admin/auth/callback.php
   ```
6. Click **Create**.
7. A dialog shows your **Client ID** and **Client Secret** — copy both now.

### Step 4 — Configure in IRM admin panel

1. Log in to the admin panel as `sa`.
2. In the sidebar expand **Authorization** → click **Settings**.
3. Fill in the form:

   | Field | Value |
   |---|---|
   | **Type** | `OpenID Connect (OIDC)` *(selected by default)* |
   | **Button Label** | `Sign in with Google` |
   | **Button Icon URL** | `https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg` |
   | **Issuer / Discovery URL** | `https://accounts.google.com` |
   | **Client ID** | Paste from Step 3 |
   | **Client Secret** | Paste from Step 3 |
   | **Scopes** | `openid email profile` *(default — leave as-is)* |
   | **Redirect URI Override** | Leave blank — auto-detected from request host |
   | **Active** | ✓ Check this box to enable the button on the login page |

4. Click **Save Configuration**.

### Step 5 — Verify

1. Open `/admin/login.php` in an incognito window.
2. You should see a **Sign in with Google** button below the username/password form.
3. Clicking it redirects to Google's consent screen.

> **Note:** The OIDC token exchange (callback handler) completes the login flow
> after Google redirects back. Ensure `/admin/auth/callback.php` is reachable
> at the redirect URI you registered.

### Troubleshooting Google OIDC

| Symptom | Fix |
|---|---|
| `redirect_uri_mismatch` error from Google | The URI in Google Console must **exactly** match the one your server generates. Check trailing slashes and `http` vs `https`. |
| Button not shown on login page | The provider must be **Active** (`is_active = 1`). Go to Auth Config → click **Activate**. |
| `No active authentication provider is configured` | Same as above — provider is saved but not activated. |
| Login works locally but fails in production | Register the production URI in Google Console under the same credential. |

---

## 12. Other OIDC Providers

Any standards-compliant OIDC provider works. Use these Issuer URLs:

| Provider | Issuer URL |
|---|---|
| Google | `https://accounts.google.com` |
| Microsoft (Azure AD) | `https://login.microsoftonline.com/{tenant-id}/v2.0` |
| Okta | `https://{your-domain}.okta.com` |
| Auth0 | `https://{your-domain}.auth0.com` |
| Keycloak | `https://{host}/realms/{realm}` |
| GitHub (via OIDC proxy) | per-provider |

The app uses **PKCE (S256)** automatically — make sure your provider supports
it (all major providers do).

---

## 13. Branding — config.json

`config/config.json` controls identity and branding. It is the **only file
you need to edit** to deploy for a different school.

```json
{
  "school": {
    "title":    "Your School Full Name",
    "subtitle": "Location — Established YYYY",
    "tagline":  "Optional one-line motto"
  },
  "brand": {
    "colors": {
      "accent": "#b5451b",
      "navy":   "#15172a"
    },
    "fonts": {
      "heading": "Crimson Text",
      "body":    "Source Sans 3"
    }
  },
  "contact": {
    "address": ["School Name", "Street", "City, State", "PIN 000000"],
    "phone":   "+91 ...",
    "email":   "contact@yourschool.org"
  },
  "social": {
    "facebook": "https://facebook.com/yourpage"
  },
  "footer": {
    "copyright":  "Copyright © 2026",
    "powered_by": "Powered by IRM"
  }
}
```

Read values in PHP with dot-notation:

```php
cfg('school.title')          // "Your School Full Name"
cfg('brand.colors.accent')   // "#b5451b"
```

---

## 14. Folder Layout

```
irm/
├── Dockerfile
├── .dockerignore
├── docker-compose.example.yml
├── env.example              # DB credentials template — copy to .env
├── config.php               # .env loader, PDO DSN helpers, session_start, h(), cfg()
├── index.php                # Public home page
├── page.php                 # Generic content page (?slug=…)
├── README.md
│
├── config/                  # ← Docker volume mount (survives rebuilds)
│   ├── config.json          # School identity & branding (edit to deploy)
│   ├── slides.json          # Carousel captions
│   ├── home.json            # Home page content
│   ├── menu.json            # Primary navigation items
│   └── external_links.json  # External link blocks
│
├── sql/
│   └── schema.sql           # DROP/CREATE auth_users + auth_config tables
│
├── includes/
│   ├── db.php               # PDO singleton — db() (sets UTC timezone)
│   ├── auth.php             # require_auth(), current_user(), PWD_REGEX
│   ├── audit.php            # audit_by() — current user ID for created_by/updated_by
│   ├── functions.php        # h(), public helper functions
│   ├── db_login.php         # auth_user_count/find/create/update functions
│   ├── db_profile.php       # auth_user_update_password/theme
│   ├── db_auth_config.php   # auth_config_get/save/clear/toggle
│   ├── db_users.php         # User management helpers
│   ├── header.php           # <head>, CSS vars, site header, primary nav
│   └── footer.php           # Footer with quick_links + contact
│
├── components/              # PHP include partials
│   └── carousel.php         # Bootstrap carousel (reads assets/img/carousel/)
│
├── admin/
│   ├── _layout.php          # Admin chrome: topbar + sidebar (requires auth)
│   ├── _layout_end.php      # Closes admin chrome, loads Bootstrap JS
│   ├── login.php            # Login + first-launch setup form
│   ├── logout.php           # Session destroy
│   ├── index.php            # Dashboard
│   ├── profile.php          # Change password + theme preference
│   ├── users.php            # User management (sa, admin)
│   ├── users_ajax.php       # AJAX handler for inline user edits
│   ├── carousel.php         # Carousel image upload + caption management
│   ├── config_general.php   # General settings — identity, theme pack (sa only)
│   ├── auth_config.php      # OIDC / SAML provider configuration (sa only)
│   ├── 403.php              # Access denied page
│   └── auth/
│       ├── redirect.php     # Builds PKCE authorization URL → redirects to provider
│       ├── callback.php     # OAuth callback handler
│       └── error.php        # OIDC provisioning error page
│
└── assets/                  # Single static-file root (CSS, images)
    ├── css/
    │   ├── site.css         # Public site styles
    │   ├── admin.css        # Admin Material Shadcn theme (light/dark, Inter font)
    │   └── themes/          # Public theme packs — drop a .css here to add one
    │       └── classic.css  # Default theme pack
    └── img/
        ├── logo.png
        └── carousel/        # ← Docker volume mount (user-uploaded images)
```

---

## 15. Database Schema

### `auth_users`

Stores admin panel accounts.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `email` | VARCHAR(255) UNIQUE NULL | `'admin'` (reserved sentinel) for the SA account; real email for all other users. |
| `name` | VARCHAR(255) NOT NULL | Display name |
| `role` | ENUM(`sa`,`admin`,`faculty`,`user`) | Default `user` |
| `password` | VARCHAR(255) NULL | bcrypt hash — SA and non-SSO users; `NULL` for SSO users. |
| `is_active` | TINYINT(1) | `0` = disabled, `1` = active |
| `sso` | TINYINT(1) | `1` = SSO-only account (no password login) |
| `theme` | ENUM(`light`,`dark`,`system`) | Per-user theme preference, default `system` |
| `created_by` | INT UNSIGNED NULL FK → `auth_users.id` | Who created the record; `NULL` = system bootstrap |
| `updated_by` | INT UNSIGNED NULL FK → `auth_users.id` | Who last modified the record; `NULL` = system |
| `created_at` | TIMESTAMP | Set once on insert; always UTC |
| `updated_at` | TIMESTAMP | Auto-updated on every change; always UTC |

> **Login identifiers:** the SA account logs in with the string `admin`; all other users log in with their email address. The login field is labelled "Email / Username".

### `auth_config`

Stores a single OIDC / SAML provider configuration.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `label` | VARCHAR(255) | Button text on login page, e.g. "Sign in with Google" |
| `icon_url` | VARCHAR(500) NULL | Icon shown next to button label |
| `type` | ENUM(`OIDC`,`SAML`) | Provider protocol |
| `issuer_url` | VARCHAR(500) | OIDC discovery URL (e.g. `https://accounts.google.com`) |
| `client_id` | VARCHAR(500) | OAuth client ID |
| `client_secret` | VARCHAR(500) | OAuth client secret (stored plaintext — protect the DB) |
| `scopes` | VARCHAR(500) | Default `openid email profile` |
| `redirect_uri` | VARCHAR(500) NULL | Override redirect URI; `NULL` = auto-detect from request |
| `is_active` | TINYINT(1) | `0` = hidden, `1` = shown on login page |
| `created_by` | INT UNSIGNED NULL FK → `auth_users.id` | Who created the config row; `NULL` = system |
| `updated_by` | INT UNSIGNED NULL FK → `auth_users.id` | Who last saved/toggled the config |
| `created_at` | TIMESTAMP | Set once on insert; always UTC |
| `updated_at` | TIMESTAMP | Auto-updated on every change; always UTC |

> Only one provider row is supported at a time (the table is replaced on save).

---

## 16. Audit Columns — created\_at / updated\_at / created\_by / updated\_by

Every table in IRM carries four standard audit columns.

### What they store

| Column | Storage | Populated by |
|---|---|---|
| `created_at` | UTC TIMESTAMP | MySQL `DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | UTC TIMESTAMP | MySQL `ON UPDATE CURRENT_TIMESTAMP` |
| `created_by` | INT UNSIGNED NULL (FK → `auth_users.id`) | `audit_by()` at insert time |
| `updated_by` | INT UNSIGNED NULL (FK → `auth_users.id`) | `audit_by()` at every write |

`NULL` in `created_by` / `updated_by` means the record was created by the system (e.g., the initial super-admin bootstrap, or a migration).

### UTC storage

All timestamps are stored in UTC. This is enforced at two levels:

1. **`sql/schema.sql`** — `SET time_zone = '+00:00'` at the top of the file so `CURRENT_TIMESTAMP` defaults are UTC when the schema is imported.
2. **`includes/db.php`** — `$pdo->exec("SET time_zone = '+00:00'")` runs on every PDO connection, regardless of the MySQL server's configured timezone.

This means you can deploy on a server running any local timezone and timestamps will always be stored consistently as UTC.

### UI display standard — show only Updated

Only `updated_at` / `updated_by` is surfaced in the admin UI. `created_at` and `created_by` are stored in the database and available for queries but are **never shown** in any table column, form footer, or detail panel.

### Display — browser local timezone with DST

Timestamps are never formatted on the server. In the admin UI, every timestamp element carries a `data-utc-ts` attribute containing the raw UTC string from the database:

```html
<span data-utc-ts="<?= h($row['updated_at']) ?>">—</span>
```

The JavaScript utility defined in `admin/_layout.php` (`window.IRM.formatUtcTs`) converts these to the **browser's local timezone** on `DOMContentLoaded`:

```javascript
// Converts "YYYY-MM-DD HH:MM:SS" (UTC) to the visitor's local time.
// Intl.DateTimeFormat uses the IANA tz database — DST transitions
// (including US EST/EDT, PST/PDT, etc.) are handled automatically.
window.IRM.formatUtcTs = function (isoStr) { ... };
```

The rendered text shows **date and time only** — no inline timezone label (avoids `GMT+5:30` clutter). The full context is available as a `title` tooltip on hover:

```
29 May 2026, 14:30 (Asia/Kolkata)  ·  2026-05-29 09:00:00 UTC
```

The tooltip carries: local formatted time · IANA timezone name (unambiguous, DST-aware) · raw UTC value.

### Adding audit columns to a new table

Follow this pattern:

**SQL:**
```sql
CREATE TABLE my_table (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    -- ... your columns ...
    created_by INT UNSIGNED  NULL,
    updated_by INT UNSIGNED  NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_my_table_created_by FOREIGN KEY (created_by) REFERENCES auth_users(id) ON DELETE SET NULL,
    CONSTRAINT fk_my_table_updated_by FOREIGN KEY (updated_by) REFERENCES auth_users(id) ON DELETE SET NULL
) DEFAULT CHARSET=utf8mb4;
```

**PHP — insert:**
```php
require_once __DIR__ . '/audit.php';   // provides audit_by()

$by = audit_by();
$st = db()->prepare(
    'INSERT INTO my_table (..., created_by, updated_by)
     VALUES (..., :created_by, :updated_by)'
);
$st->execute([..., ':created_by' => $by, ':updated_by' => $by]);
```

**PHP — update:**
```php
$st = db()->prepare(
    'UPDATE my_table SET field = :val, updated_by = :by WHERE id = :id'
);
$st->execute([':val' => $value, ':by' => audit_by(), ':id' => $id]);
```

**PHP — read (join for display names):**
```php
$rows = db()->query(
    'SELECT t.*,
            c.name  AS created_by_name,
            up.name AS updated_by_name
     FROM my_table t
     LEFT JOIN auth_users c  ON c.id  = t.created_by
     LEFT JOIN auth_users up ON up.id = t.updated_by'
)->fetchAll();
```

**HTML — display timestamp (Updated only):**
```html
<span data-utc-ts="<?= h($row['updated_at']) ?>">—</span>
<!-- JS auto-replaces "—" with local time; tooltip shows full tz + UTC -->
<?php if (!empty($row['updated_by_name'])): ?>
  <div style="font-size:.7rem;opacity:.7"><?= h($row['updated_by_name']) ?></div>
<?php endif; ?>
```

> `created_at` / `created_by` are stored but not rendered. Do not add a "Created" column or line to new UI — see ADR-0015.

---

## 17. Security Notes

- **CSRF**: Every admin POST form carries a per-session CSRF token
  (`$_SESSION['csrf']`). The token is validated with `hash_equals()` before
  any state change.
- **SQL injection**: All queries use PDO prepared statements with named
  placeholders. No string interpolation in SQL.
- **XSS**: All output is escaped with `h()` (`htmlspecialchars`). Admin-authored
  HTML content (e.g. page body) is the only intentional exception.
- **PKCE**: The OIDC flow uses PKCE (S256) — the code verifier is never sent
  to the browser.
- **Password policy**: `PWD_REGEX` enforces ≥ 8 chars, 1 uppercase, 1 digit,
  1 special character.
- **Secrets in env only**: `config.json` contains no secrets. All credentials
  live in `.env` (gitignored).
- **`.env` file access**: Block web access to `.env` in your web server config
  or place it above the document root.
- **`APP_DEBUG=false` in production**: Never expose stack traces publicly.
- **HTTPS required for OIDC**: OAuth 2.0 redirect URIs must use HTTPS in
  production. Browsers also block cookies on HTTP in some contexts.

---

## 18. PHP Configuration

### Required PHP Extensions

IRM requires three PHP extensions. Enable them in `php.ini` by removing the
leading `;` from the relevant lines.

| Extension | Why it's needed | `php.ini` line |
|---|---|---|
| `openssl` | OIDC/PKCE — HTTPS requests to the provider's discovery endpoint and token exchange | `extension=openssl` |
| `pdo_mysql` | All database access via PDO | `extension=pdo_mysql` |
| `pdo_odbc` | Optional — needed only if connecting through an ODBC data source | `extension=pdo_odbc` |

```ini
; in php.ini — uncomment these three lines (remove the leading semicolon)
extension=openssl
extension=pdo_mysql
extension=pdo_odbc
```

On Windows the extension DLLs live in the `ext/` folder next to `php.exe`.
Ensure `extension_dir` in `php.ini` points to that folder:

```ini
extension_dir = "C:\php-8.5.6\ext"
```

Restart PHP / PHP-FPM after any extension change. Verify with:

```bash
php -m | findstr /I "openssl pdo_mysql pdo_odbc"
```

---

### Upload Limits

The application enforces a configurable upload limit. The **single source of
truth** is `UPLOAD_MAX_BYTES` in `.env` (default `5242880` = 5 MB). Both the
PHP server-side check and the client-side drop zone pre-validation read this
value — change it in one place and both are updated.

> **PHP also has its own limits** (`upload_max_filesize`, `post_max_size` in
> `php.ini`). These are enforced by PHP itself, *before* the application code
> runs. If `UPLOAD_MAX_BYTES` is higher than `upload_max_filesize`, PHP will
> silently truncate or reject large files. **Keep the php.ini limits ≥
> `UPLOAD_MAX_BYTES`.**

The default php.ini value (`upload_max_filesize = 2M`) is lower than the 5 MB
application default — raise it using whichever method matches your deployment.

### Option A — `php.ini` (recommended)

Applies to: `php -S`, PHP-FPM, IIS with PHP, Docker.

Find the active `php.ini`:

```bash
php --ini
# or open a PHP page containing: <?php phpinfo(); ?>
```

> On Windows with the PHP installer the file is typically
> `C:\php-x.x.x\php.ini`

Add or update these two directives (adjust the values to match or exceed your
`UPLOAD_MAX_BYTES` setting):

```ini
upload_max_filesize = 10M
post_max_size       = 12M
```

> `post_max_size` must be larger than `upload_max_filesize` — it covers the
> entire multipart body (file bytes + form fields).

Restart PHP or PHP-FPM after saving.

#### Docker — custom `php.ini` drop-in

Place an override file in the container's `conf.d` directory instead of
editing the base image's `php.ini`:

```dockerfile
COPY docker/php-uploads.ini /usr/local/etc/php/conf.d/uploads.ini
```

`docker/php-uploads.ini`:

```ini
upload_max_filesize = 10M
post_max_size       = 12M
```

### Option B — `.htaccess` (Apache with `mod_php` only)

Create `.htaccess` in the project root:

```apache
php_value upload_max_filesize 10M
php_value post_max_size 12M
```

This overrides `php.ini` per-directory without a server restart and requires
`AllowOverride All` in the Apache `<Directory>` block.

> **Does not work** with PHP-FPM, Nginx, or `php -S`. Use Option A for
> those setups.

### Verifying the active limits

```php
<?php
echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;
echo 'post_max_size: '       . ini_get('post_max_size')       . PHP_EOL;
```

Or check the **PHP Info** page (`<?php phpinfo(); ?>`).

---

## 19. Troubleshooting

### Blank page / 500 error

Set `APP_DEBUG=true` in `.env` temporarily to see the PHP error, then set it
back to `false`.

### "Connection refused" / DB errors

1. Verify `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` in `.env`.
2. Confirm the DB server is running: `mysql -h $DB_HOST -u $DB_USER -p`.
3. In Docker, use `host.docker.internal` (Mac/Windows) or `172.17.0.1` (Linux)
   instead of `127.0.0.1` to reach a host-side MySQL.

### Schema not imported

Run: `mysql -u <user> -p <dbname> < sql/schema.sql`

> **Warning:** `schema.sql` uses `DROP TABLE IF EXISTS` — it will erase
> existing auth data. Only run it on a fresh install or when you intend to
> reset.

### Login page shows setup screen again after setup

The setup screen appears when `auth_users` is empty. If setup keeps repeating,
the `INSERT` into `auth_users` may have failed — check DB errors with
`APP_DEBUG=true`.

### Google OIDC — `redirect_uri_mismatch`

The URI registered in Google Console must **exactly** match what the server
sends. IRM auto-builds it as `{scheme}://{HTTP_HOST}/admin/auth/callback.php`.
Add that exact URI in the Google Console credential.

### Theme flicker on load (system mode)

The system-mode pages include an inline `<script>` that sets
`data-bs-theme` before the first paint. If you see a flash, ensure the
`<script>` tag is in `<head>` **before** the Bootstrap CSS link (it is, by
default).
