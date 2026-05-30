<?php

declare(strict_types=1);

/**
 * index.php — public Home page
 */

require_once __DIR__ . '/config.php';

$page_title  = 'Home';
$active_slug = 'home';

// --- Home copy from config/home.json (deployer-managed static text) ---
$home_raw = is_readable(__DIR__ . '/config/home.json')
    ? file_get_contents(__DIR__ . '/config/home.json')
    : '{}';
$home = json_decode((string)$home_raw, true) ?? [];

// --- News ---
$news_raw = is_readable(__DIR__ . '/config/news.json')
    ? file_get_contents(__DIR__ . '/config/news.json')
    : '[]';
$news = array_slice(json_decode((string)$news_raw, true) ?? [], 0, 8);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-3">
  <div class="row">
    <?php $layout = 'full'; require __DIR__ . '/public/components/carousel.php'; ?>
  </div>
</div>

<section class="hero container">

  <!-- ===== Welcome / About column ===== -->
  <div class="intro">
    <div class="intro-block">
      <div class="seal" aria-hidden="true">
        <div class="seal-inner">
          <span class="swan">⛅</span>
          RAMA-<br>KRISHNA<br>MISSION
        </div>
      </div>
      <div class="intro-text">
        <p><?= nl2br(h((string)($home['about'] ?? ''))) ?></p>
      </div>
    </div>

    <?php if (!empty($home['welcome'])): ?>
      <h2 class="welcome-heading"><?= h((string)($home['welcome']['heading'] ?? '')) ?></h2>
      <div class="body-text">
        <?php foreach ((array)($home['welcome']['body'] ?? []) as $para): ?>
          <p><?= h((string)$para) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</section>
</div><!-- /.band-cream -->

<!-- ============ Lower band (white) ============ -->
<div class="band-paper">

  <!-- ===== Sections grid ===== -->
  <?php $section_cols = (int)($home['section_cols'] ?? 4); ?>
  <section class="container four-col" data-cols="<?= $section_cols ?>">
    <?php foreach ((array)($home['sections'] ?? []) as $sec):
      $type = $sec['type'] ?? 'text';
    ?>
    <div class="col col--<?= h($type) ?>">
      <h3 class="sec-h"><?= h((string)($sec['heading'] ?? '')) ?></h3>

      <?php if ($type === 'text'): ?>
        <?php if (!empty($sec['img'])): ?>
          <img class="card-img" src="<?= h((string)$sec['img']) ?>" alt="<?= h((string)($sec['heading'] ?? '')) ?>" onerror="this.style.display='none'" />
        <?php endif; ?>
        <div class="col-body">
          <?php foreach ((array)($sec['body'] ?? []) as $para): ?>
            <p><?= h((string)$para) ?></p>
          <?php endforeach; ?>
        </div>

      <?php elseif ($type === 'video'): ?>
        <?php $provider = $sec['provider'] ?? 'youtube'; $embed_url = (string)($sec['url'] ?? ''); ?>
        <?php if ($embed_url !== ''): ?>
          <div class="video-embed">
            <iframe src="<?= h($embed_url) ?>"
                    title="<?= h((string)($sec['heading'] ?? '')) ?>"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen loading="lazy"></iframe>
          </div>
        <?php else: ?>
          <div class="video">
            <div class="video-inner">
              <button class="play-btn" aria-label="Play">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M8 5v14l11-7z"/></svg>
              </button>
            </div>
            <div class="video-yt">▶ <?= h(ucfirst($provider)) ?></div>
          </div>
        <?php endif; ?>
        <?php if (!empty($sec['body'])): ?>
          <div class="col-body">
            <?php foreach ((array)$sec['body'] as $para): ?>
              <p><?= h((string)$para) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($type === 'noticeboard'): ?>
        <div class="component-placeholder" data-component="noticeboard"></div>

      <?php elseif ($type === 'links'): ?>
        <div class="component-placeholder" data-component="links"></div>

      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </section>

  <!-- ===== Partners strip ===== -->
  <?php if (!empty($home['partners'])): ?>
  <div class="partners-strip">
    <div class="container">
      <?php foreach ((array)$home['partners'] as $p): ?>
        <div class="partner">
          <img class="partner-logo" src="<?= h((string)$p['img']) ?>" alt="<?= h((string)$p['alt']) ?>" onerror="this.style.display='none'" />
          <div class="partner-label"><?= h((string)$p['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== Latest News ===== -->
  <section class="container latest">
    <div>
      <h3 class="sec-h">Latest News &amp; Events</h3>
      <ul class="news-list">
        <?php foreach ($news as $n): ?>
          <li>
            <a href="news.php?slug=<?= urlencode((string)$n['slug']) ?>"><?= h((string)$n['title']) ?></a>
            <div style="font-size:11.5px;color:#7a7666;margin-top:2px;">
              <?= h(date('j M Y', strtotime((string)$n['published_at']))) ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
