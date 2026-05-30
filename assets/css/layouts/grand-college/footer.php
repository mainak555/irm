<?php
declare(strict_types=1);
/*
 * Grand College layout — public footer partial.
 * Called from includes/footer.php before Bootstrap JS is loaded.
 * In scope: cfg(), h() available.
 */
$_gc_quick_links = cfg('footer.quick_links', []);
$_gc_facebook    = cfg('general.social.facebook',  '');
$_gc_twitter     = cfg('general.social.twitter',   '');
$_gc_instagram   = cfg('general.social.instagram', '');
$_gc_youtube     = cfg('general.social.youtube',   '');
$_gc_site_title  = cfg('general.title', 'School CMS');
?>
<footer class="gc-footer">
  <div class="container">
    <div class="gc-footer-grid">

      <div class="gc-footer-brand">
        <div class="gc-footer-logo"><?= h($_gc_site_title) ?></div>
        <?php if ($sub = cfg('general.subtitle', '')): ?>
          <p class="gc-footer-tagline"><?= h($sub) ?></p>
        <?php endif; ?>
        <?php if ($_gc_facebook || $_gc_twitter || $_gc_instagram || $_gc_youtube): ?>
        <div class="gc-footer-social">
          <?php if ($_gc_facebook): ?>
            <a href="<?= h($_gc_facebook) ?>" aria-label="Facebook" target="_blank" rel="noopener">f</a>
          <?php endif; ?>
          <?php if ($_gc_twitter): ?>
            <a href="<?= h($_gc_twitter) ?>" aria-label="Twitter / X" target="_blank" rel="noopener">𝕏</a>
          <?php endif; ?>
          <?php if ($_gc_instagram): ?>
            <a href="<?= h($_gc_instagram) ?>" aria-label="Instagram" target="_blank" rel="noopener">ig</a>
          <?php endif; ?>
          <?php if ($_gc_youtube): ?>
            <a href="<?= h($_gc_youtube) ?>" aria-label="YouTube" target="_blank" rel="noopener">yt</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($_gc_quick_links)): ?>
      <div class="gc-footer-links">
        <h4>Quick Links</h4>
        <ul>
          <?php foreach ($_gc_quick_links as $_gl): ?>
            <li><a href="<?= h($_gl['url']) ?>"><?= h($_gl['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="gc-footer-contact">
        <h4>Contact Us</h4>
        <p><?= nl2br(h(cfg('general.address', ''))) ?></p>
        <p><strong>Tel:</strong> <?= h(cfg('general.phone', '')) ?></p>
        <?php if ($_gc_fax = cfg('general.fax', '')): ?>
          <p><strong>Fax:</strong> <?= h($_gc_fax) ?></p>
        <?php endif; ?>
        <p><strong>Email:</strong> <?= h(cfg('general.email', '')) ?></p>
      </div>

    </div>
  </div>

  <div class="gc-footer-bar">
    <div class="container">
      <span><?= h(cfg('footer.copyright', '')) ?></span>
      <span><?= h(cfg('footer.powered_by', '')) ?></span>
    </div>
  </div>
</footer>
