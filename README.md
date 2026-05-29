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
16. [Security Notes](#16-security-notes)
17. [Troubleshooting](#17-troubleshooting)

---

## 1. Prerequisites

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 8.0 | 8.2+ |
| PHP extensions | `pdo_mysql` | `pdo_mysql`, `mbstring` |
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
| **Username** | Pre-filled as `admin` — this is the super-admin (`sa`) account. Cannot be changed here. |
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
| **Users** | `/admin/users.php` | `sa`, `admin` | User management *(coming soon)* |
| **Auth Config** | `/admin/auth_config.php` | `sa` only | Configure OIDC / SAML SSO provider |
| **Logout** | `/admin/logout.php` | All roles | Destroy session |

### Navbar

The top navbar shows:
- **Site title** (from `config/config.json`)
- **Logged-in user name + role badge**
- **Theme switcher** — Light / Dark / System (saves immediately)
- **Logout** button

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
are created via the Users screen (once implemented).

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
├── env.example              # DB credentials template — copy to .env
├── config.php                # .env loader, PDO DSN helpers, session_start, h(), cfg()
├── index.php                 # Public home page
├── page.php                  # Generic content page (?slug=…)
├── README.md
│
├── config/
│   └── config.json           # School identity & branding (edit to deploy)
│
├── sql/
│   └── schema.sql            # DROP/CREATE auth_users + auth_config tables
│
├── includes/
│   ├── db.php                # PDO singleton — db()
│   ├── auth.php              # require_auth(), current_user(), PWD_REGEX
│   ├── functions.php         # h(), public helper functions
│   ├── db_login.php          # auth_user_count/find/create/update functions
│   ├── db_profile.php        # auth_user_update_password/theme
│   ├── db_auth_config.php    # auth_config_get/save/clear/toggle
│   └── header.php / footer.php
│
├── admin/
│   ├── _layout.php           # Admin chrome: topbar + sidebar (requires auth)
│   ├── _layout_end.php       # Closes admin chrome, loads Bootstrap JS
│   ├── style.css             # Material Shadcn theme (light/dark tokens, Inter font)
│   ├── login.php             # Login + first-launch setup form
│   ├── logout.php            # Session destroy
│   ├── index.php             # Dashboard
│   ├── profile.php           # Change password + theme preference
│   ├── users.php             # User management (sa, admin)
│   ├── auth_config.php       # OIDC / SAML provider configuration (sa only)
│   ├── 403.php               # Access denied page
│   └── auth/
│       ├── redirect.php      # Builds PKCE authorization URL → redirects to provider
│       └── callback.php      # OAuth callback handler (receives code from provider)
│
└── assets/
    └── css/
        └── site.css          # Public site styles
```

---

## 15. Database Schema

### `auth_users`

Stores admin panel accounts.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `username` | VARCHAR(50) UNIQUE NULL | Only set for the `sa` account (`admin`). OIDC users have `NULL`. |
| `email` | VARCHAR(255) UNIQUE NULL | `NULL` for `sa`; required for OIDC users. |
| `name` | VARCHAR(255) NOT NULL | Display name |
| `role` | ENUM(`sa`,`admin`,`faculty`,`user`) | Default `user` |
| `password` | VARCHAR(255) NULL | bcrypt hash — only for `sa`. OIDC users have `NULL`. |
| `is_active` | TINYINT(1) | `0` = disabled, `1` = active |
| `theme` | ENUM(`light`,`dark`,`system`) | Per-user theme preference, default `system` |
| `created_at` / `updated_at` | TIMESTAMP | Auto-managed |

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
| `created_at` / `updated_at` | TIMESTAMP | Auto-managed |

> Only one provider row is supported at a time (the table is replaced on save).

---

## 16. Security Notes

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

## 17. Troubleshooting

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
