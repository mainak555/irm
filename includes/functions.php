<?php
/**
 * includes/functions.php
 * Shared CMS helpers: settings lookup, menu loader, links loader,
 * escaping, auth checks.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Public menu items, in display order. Returns rows with label, slug, target. */
function public_menu(): array {
    $stmt = db()->query("
        SELECT id, label, slug, page_target, is_external, sort_order
        FROM menus
        WHERE is_public = 1
        ORDER BY sort_order ASC, id ASC
    ");
    return $stmt->fetchAll();
}


/** Latest news/events ordered by published_at desc. */
function latest_news(int $limit = 10): array {
    $stmt = db()->prepare("
        SELECT id, title, slug, published_at
        FROM news
        WHERE is_public = 1 AND published_at <= NOW()
        ORDER BY published_at DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Carousel slides for the hero. */
function hero_slides(): array {
    $stmt = db()->query("
        SELECT id, image_path, caption, sort_order
        FROM hero_slides
        WHERE is_public = 1
        ORDER BY sort_order ASC, id ASC
    ");
    return $stmt->fetchAll();
}

/** Content blocks (named editable HTML chunks: 'welcome', 'life', 'eco', etc.) */
function block(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $rows = db()->query("SELECT block_key, body_html FROM content_blocks WHERE is_public = 1")->fetchAll();
        foreach ($rows as $r) $cache[$r['block_key']] = $r['body_html'];
    }
    return $cache[$key] ?? $default;
}

// ---------- Auth ----------

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** CSRF helpers. */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    $sent = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        die('Bad CSRF token. Please reload and try again.');
    }
}
