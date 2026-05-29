<?php
/**
 * config.php
 * Loads environment variables (from real env vars OR a local .env file)
 * and exposes them via env() helper.
 *
 * The SQL connection string is read from environment ONLY.
 * No credentials are hard-coded in this file.
 */

declare(strict_types=1);

// ---- Lightweight .env loader (used in dev when real env vars aren't set) ----
function load_dotenv(string $path): void {
    if (!is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        // Strip surrounding quotes
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[strlen($v)-1] === $v[0]) {
            $v = substr($v, 1, -1);
        }
        if (getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

load_dotenv(__DIR__ . '/.env');

function env(string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v === false || $v === '') {
        return $_ENV[$key] ?? $default;
    }
    return $v;
}

// ---- Database connection helpers (reads DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS from .env) ----
function db_dsn(): string {
    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $name = env('DB_NAME', 'irm');
    return "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
}

function db_user(): string { return env('DB_USER') ?? ''; }
function db_pass(): string { return env('DB_PASS') ?? ''; }

define('APP_DEBUG',  strtolower(env('APP_DEBUG',  'false') ?? 'false') === 'true');
define('APP_SECRET', env('APP_SECRET', 'change-me') ?? 'change-me');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- HTML escaping ----
function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---- Build a URL for a nav/menu item ----
function menu_url(array $m): string {
    if ((int)($m['is_external'] ?? 0) === 1) return (string)$m['page_target'];
    if ($m['slug'] === 'home') return 'index.php';
    return 'page.php?slug=' . urlencode((string)$m['slug']);
}

// ---- Site/content configuration (config/config.json) ----
function cfg(string $key, mixed $default = null): mixed
{
    static $data = null;

    if ($data === null) {
        $path = __DIR__ . '/config/config.json';
        $raw  = is_readable($path) ? file_get_contents($path) : false;
        if ($raw === false) {
            error_log('cfg(): config/config.json is unreadable');
            $data = [];
        } else {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                error_log('cfg(): config/config.json is malformed (json_decode returned null)');
                $data = [];
            } else {
                $data = $decoded;
            }
        }
    }

    $parts   = explode('.', $key);
    $current = $data;
    foreach ($parts as $part) {
        if (!is_array($current) || !array_key_exists($part, $current)) {
            return $default;
        }
        $current = $current[$part];
    }
    return $current;
}
