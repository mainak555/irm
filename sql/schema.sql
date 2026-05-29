-- =========================================================
--  IRM School CMS — Auth Tables
--  MySQL 5.7+ / MariaDB 10.3+ / utf8mb4
--
--  Run this file to install the auth foundation.
--  WARNING: DROP IF EXISTS will destroy existing auth data.
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+00:00';

USE irm;

DROP TABLE IF EXISTS auth_config;
DROP TABLE IF EXISTS auth_users;

-- Live migration for existing installs (run before deploying new code):
--   UPDATE auth_users SET email = 'admin' WHERE username = 'admin' AND role = 'sa';
--   ALTER TABLE auth_users DROP COLUMN username;

CREATE TABLE auth_users (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255)    NULL UNIQUE,            -- 'admin' sentinel for SA; real email for all other users
    name        VARCHAR(255)    NOT NULL,
    role        ENUM('sa','admin','faculty','user') NOT NULL DEFAULT 'user',
    password    VARCHAR(255)    NULL,                   -- bcrypt hash; sa only; NULL for OIDC users
    is_active   TINYINT(1)      NOT NULL DEFAULT 1,
    sso         TINYINT(1)      NOT NULL DEFAULT 0,
    theme       ENUM('light','dark','system') NOT NULL DEFAULT 'system',
    created_by  INT UNSIGNED    NULL,                   -- NULL = system / bootstrap
    updated_by  INT UNSIGNED    NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES auth_users(id) ON DELETE SET NULL,
    CONSTRAINT fk_users_updated_by FOREIGN KEY (updated_by) REFERENCES auth_users(id) ON DELETE SET NULL
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE auth_config (
    id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    label         VARCHAR(255)  NOT NULL,               -- e.g. "Login with Google"
    icon_url      VARCHAR(500)  NULL,                   -- provider icon for login button
    type          ENUM('OIDC','SAML') NOT NULL,
    issuer_url    VARCHAR(500)  NOT NULL,               -- OIDC discovery URL or SAML entity ID
    client_id     VARCHAR(500)  NOT NULL,
    client_secret VARCHAR(500)  NOT NULL,               -- always rendered masked in admin UI
    scopes        VARCHAR(500)  NOT NULL DEFAULT 'openid email profile',
    redirect_uri  VARCHAR(500)  NULL,                   -- override if needed; NULL = use default
    is_active     TINYINT(1)    NOT NULL DEFAULT 0,     -- must be explicitly enabled after config
    created_by    INT UNSIGNED  NULL,                   -- NULL = system / bootstrap
    updated_by    INT UNSIGNED  NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_config_created_by FOREIGN KEY (created_by) REFERENCES auth_users(id) ON DELETE SET NULL,
    CONSTRAINT fk_config_updated_by FOREIGN KEY (updated_by) REFERENCES auth_users(id) ON DELETE SET NULL
) DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
