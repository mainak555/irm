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
    $item['children']    = $item['children']    ?? [];
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/site.css" />
<?php
$_irm_theme_slug = basename((string)cfg('public.theme', 'classic'));
$_irm_theme_url  = '';
if ($_irm_theme_slug !== '') {
    $_folder_file = __DIR__ . '/../assets/css/themes/' . $_irm_theme_slug . '/theme.css';
    $_flat_file   = __DIR__ . '/../assets/css/themes/' . $_irm_theme_slug . '.css';
    if (is_file($_folder_file)) {
        $_irm_theme_url = '/assets/css/themes/' . h($_irm_theme_slug) . '/theme.css';
    } elseif (is_file($_flat_file)) {
        $_irm_theme_url = '/assets/css/themes/' . h($_irm_theme_slug) . '.css';
    }
}
if ($_irm_theme_url === '') {
    $_irm_theme_url = '/assets/css/themes/classic.css';
}
?>
<link rel="stylesheet" href="<?= $_irm_theme_url ?>" />
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
<?php
// Load theme fonts from manifest if available
$_irm_font_manifest = __DIR__ . '/../assets/css/themes/' . $_irm_theme_slug . '/manifest.json';
if (is_file($_irm_font_manifest)) {
    $_tm = json_decode((string)file_get_contents($_irm_font_manifest), true) ?? [];
    $_theme_fonts = is_array($_tm['fonts'] ?? null) ? $_tm['fonts'] : [];
    if (!empty($_theme_fonts)) {
        $__fq = implode('&family=', array_map(fn($f) => str_replace(' ', '+', $f), $_theme_fonts));
        echo '<link href="https://fonts.googleapis.com/css2?family=' . $__fq . '&display=swap" rel="stylesheet">' . "\n";
    }
}
?>
<?php if (!empty($extra_head)) { echo $extra_head; } ?>
</head>
<body>

<?php
// Dispatch to layout pack header if it exists, else render generic fallback.
$_layout_pack_key   = basename((string)cfg('public.layout', ''));
$_layout_header_php = $_layout_pack_key !== ''
    ? __DIR__ . '/../assets/css/layouts/' . $_layout_pack_key . '/header.php'
    : '';
if ($_layout_header_php !== '' && is_file($_layout_header_php)) {
    require $_layout_header_php;
} else { ?>
<div class="band-cream">
  <header class="site-header container">
    <h1 class="school-title"><?= h($site_title) ?></h1>
    <?php if ($site_subtitle): ?>
      <div class="school-sub"><?= h($site_subtitle) ?></div>
    <?php endif; ?>
  </header>

  <nav class="primary">
    <ul>
      <?php foreach ($nav_items as $m):
        $children     = (array)($m['children'] ?? []);
        $has_children = !empty($children);
        $child_slugs  = array_column($children, 'slug');
        $is_active    = $m['slug'] === $active_slug || in_array($active_slug, $child_slugs, true);
        $ext_attrs    = (int)$m['is_external'] === 1 ? ' target="_blank" rel="noopener"' : '';
      ?>
        <?php if ($has_children): ?>
        <li class="nav-item dropdown">
          <a href="#"
             class="<?= $is_active ? 'active' : '' ?> dropdown-toggle"
             data-bs-toggle="dropdown"
             aria-expanded="false"<?= $ext_attrs ?>>
            <?= h($m['label']) ?>
          </a>
          <ul class="dropdown-menu">
            <?php foreach ($children as $child):
              $child_ext = (int)($child['is_external'] ?? 0) === 1 ? ' target="_blank" rel="noopener"' : '';
            ?>
            <li>
              <a class="dropdown-item <?= ($child['slug'] ?? '') === $active_slug ? 'active' : '' ?>"
                 href="<?= h(menu_url($child)) ?>"<?= $child_ext ?>>
                <?= h($child['label']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </li>
        <?php else: ?>
        <li>
          <a href="<?= h(menu_url($m)) ?>"
             class="<?= $is_active ? 'active' : '' ?>"<?= $ext_attrs ?>>
            <?= h($m['label']) ?>
          </a>
        </li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </nav>
</div>
<?php } ?>
