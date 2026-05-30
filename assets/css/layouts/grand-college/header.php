<?php
declare(strict_types=1);
/*
 * Grand College layout — public header/nav partial.
 * Called from includes/header.php after <body> is opened.
 * In scope: $nav_items, $site_title, $site_subtitle, $active_slug, h(), cfg(), menu_url()
 */
?>
<header class="gc-header">
  <div class="gc-header-inner container">
    <a href="/" class="gc-logo">
      <?php if (cfg('general.logoUrl', '') !== ''): ?>
        <img src="<?= h(cfg('general.logoUrl', '')) ?>" alt="<?= h($site_title) ?>" class="gc-logo-img">
      <?php else: ?>
        <span class="gc-logo-text"><?= h($site_title) ?></span>
        <?php if ($site_subtitle): ?>
          <small class="gc-logo-sub"><?= h($site_subtitle) ?></small>
        <?php endif; ?>
      <?php endif; ?>
    </a>

    <button class="gc-nav-toggle navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#gcNavMenu"
            aria-controls="gcNavMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span></span><span></span><span></span>
    </button>

    <nav class="collapse navbar-collapse" id="gcNavMenu">
      <ul class="gc-nav-list">
        <?php foreach ($nav_items as $m):
          $children     = (array)($m['children'] ?? []);
          $has_children = !empty($children);
          $child_slugs  = array_column($children, 'slug');
          $is_active    = $m['slug'] === $active_slug || in_array($active_slug, $child_slugs, true);
          $ext_attrs    = (int)$m['is_external'] === 1 ? ' target="_blank" rel="noopener"' : '';
        ?>
          <?php if ($has_children): ?>
          <li class="gc-nav-item gc-has-dropdown">
            <a href="#"
               class="gc-nav-link<?= $is_active ? ' active' : '' ?>"
               data-bs-toggle="dropdown" aria-expanded="false"<?= $ext_attrs ?>>
              <?= h($m['label']) ?> <span class="gc-caret">▾</span>
            </a>
            <ul class="gc-dropdown">
              <?php foreach ($children as $child):
                $child_ext = (int)($child['is_external'] ?? 0) === 1 ? ' target="_blank" rel="noopener"' : '';
              ?>
              <li>
                <a class="gc-dropdown-item<?= ($child['slug'] ?? '') === $active_slug ? ' active' : '' ?>"
                   href="<?= h(menu_url($child)) ?>"<?= $child_ext ?>>
                  <?= h($child['label']) ?>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
          </li>
          <?php else: ?>
          <li class="gc-nav-item">
            <a href="<?= h(menu_url($m)) ?>"
               class="gc-nav-link<?= $is_active ? ' active' : '' ?>"<?= $ext_attrs ?>>
              <?= h($m['label']) ?>
            </a>
          </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>
