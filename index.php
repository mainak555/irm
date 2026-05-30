<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/page_renderer.php';

const RESERVED_SLUGS = ['admin', 'assets', 'config', 'includes', 'components', 'api'];

$slug = (string)($_GET['p'] ?? 'home');

// Validate: lowercase alphanumeric and hyphens only
if (!preg_match('/^[a-z0-9-]+$/', $slug) || in_array($slug, RESERVED_SLUGS, true)) {
    http_response_code(404);
    require __DIR__ . '/includes/header.php';
    echo '<div class="container my-5"><h2>Page Not Found</h2><p>The requested page does not exist.</p></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_file = __DIR__ . '/config/public/' . $slug . '.json';

if (!is_readable($page_file)) {
    http_response_code(404);
    $page_title = 'Page Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container my-5"><h2>Page Not Found</h2><p>The requested page does not exist.</p></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_raw  = (string)file_get_contents($page_file);
$page_data = json_decode($page_raw, true) ?? [];

$page_title       = (string)($page_data['title']       ?? '');
$page_description = (string)($page_data['description'] ?? '');
$active_slug      = $slug;

// Build canonical URL
$scheme           = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host             = (string)($_SERVER['HTTP_HOST'] ?? '');
$canonical_url    = $scheme . '://' . $host . '/' . $slug;

// Additional <head> tags injected via $extra_head picked up by header.php
$extra_head = '';
if ($page_description !== '') {
    $extra_head .= '<meta name="description" content="' . h($page_description) . '">' . "\n";
}
$extra_head .= '<link rel="canonical" href="' . h($canonical_url) . '">' . "\n";

require __DIR__ . '/includes/header.php';
render_page($page_data);
require_once __DIR__ . '/includes/footer.php';
