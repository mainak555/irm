<?php

declare(strict_types=1);

/**
 * includes/header.php
 * Full page chrome: <head>, CSS vars, header title, primary nav, opens <body>.
 * Does NOT close <body> — that is footer.php's responsibility.
 *
 * Vars expected in scope (optional):
 *   $page_title  — page-specific <title> prefix; falls back to school title
 *   $active_slug — slug of the current page to highlight in nav
 */

require_once __DIR__ . '/../config.php';

$site_title    = cfg('general.title', 'School CMS');
$site_subtitle = cfg('general.subtitle', '');
$page_title    = $page_title ?? $site_title;
$active_slug   = $active_slug ?? 'home';

// Nav from config/menu.json
$raw       = is_readable(__DIR__ . '/../config/menu.json')
    ? file_get_contents(__DIR__ . '/../config/menu.json')
    : '[]';
$menu_data = json_decode((string)$raw, true);
$nav_items = is_array($menu_data) ? $menu_data : [];
foreach ($nav_items as &$item) {
    $item['is_external'] = $item['is_external'] ?? 0;
    $item['page_target'] = $item['page_target'] ?? '';
}
unset($item);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= h($page_title) ?> — <?= h($site_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Crimson+Text:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/site.css" />
<?php
$_irm_theme_slug = cfg('public.theme', 'classic');
$_irm_theme_file = __DIR__ . '/../public/css/themes/' . basename($_irm_theme_slug) . '.css';
if ($_irm_theme_slug === '' || !is_file($_irm_theme_file)) {
    $_irm_theme_slug = 'classic';
}
?>
<link rel="stylesheet" href="/public/css/themes/<?= h($_irm_theme_slug) ?>.css" />
<?php
$colors = cfg('colors', []);
if (is_array($colors) && $colors):
?>
<style>
:root {
<?php foreach ($colors as $prop => $val): ?>
  --<?= h((string)$prop) ?>: <?= h((string)$val) ?>;
<?php endforeach; ?>
}
</style>
<?php endif; ?>
</head>
<body>

<div class="band-cream">
  <header class="site-header container">
    <h1 class="school-title"><?= h($site_title) ?></h1>
    <?php if ($site_subtitle): ?>
      <div class="school-sub"><?= h($site_subtitle) ?></div>
    <?php endif; ?>
  </header>

  <nav class="primary">
    <ul>
      <?php foreach ($nav_items as $m): ?>
        <li>
          <a href="<?= h(menu_url($m)) ?>"
             class="<?= $m['slug'] === $active_slug ? 'active' : '' ?>"
             <?= (int)$m['is_external'] === 1 ? 'target="_blank" rel="noopener"' : '' ?>>
            <?= h($m['label']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
