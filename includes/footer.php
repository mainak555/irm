<?php

declare(strict_types=1);

/**
 * includes/footer.php
 * Renders the dark footer + bottom bar and closes </body></html>.
 * Quick links and address come from config/config.json via cfg().
 */

require_once __DIR__ . '/../config.php';

$quick_links = cfg('footer.quick_links', []);
$facebook    = cfg('school.social.facebook', '');
?>
<footer>
  <div class="container foot-grid">
    <div>
      <h4>Quick Links</h4>
      <ul>
        <?php foreach ($quick_links as $link): ?>
          <li><a href="<?= h($link['url']) ?>"><?= h($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="foot-address">
      <h4>How To Find Us</h4>
      <p><?= nl2br(h(cfg('school.address', ''))) ?></p>
      <p style="margin-top:10px;"><strong>Tel:</strong> <?= h(cfg('school.phone', '')) ?></p>
      <?php if ($fax = cfg('school.fax', '')): ?>
        <p><strong>Fax:</strong> <?= h($fax) ?></p>
      <?php endif; ?>
      <p><strong>Email:</strong> <?= h(cfg('school.email', '')) ?></p>
    </div>

    <div>
      <h4>Connect With Us</h4>
      <div class="foot-social">
        <?php if ($facebook): ?>
          <a href="<?= h($facebook) ?>" aria-label="Facebook" target="_blank" rel="noopener">f</a>
        <?php endif; ?>
      </div>
      <p style="margin-top:14px; color:#9d9c97; font-size:11.5px;">
        Visit us on Facebook for the latest announcements, photographs and student stories.
      </p>
    </div>
  </div>

  <div class="foot-bar">
    <div class="container">
      <span><?= h(cfg('footer.copyright', '')) ?></span>
      <span><?= h(cfg('footer.powered_by', '')) ?></span>
    </div>
  </div>
</footer>

</body>
</html>
