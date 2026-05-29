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

// --- Carousel: folder-first + DB caption overlay ---
$folder_slides = glob(__DIR__ . '/assets/img/carousel/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
if (!is_array($folder_slides)) {
    $folder_slides = [];
}
natsort($folder_slides);
$folder_slides = array_values($folder_slides);

// Fetch hero_slides for caption overlay and JSON-only slides
$slides_raw = is_readable(__DIR__ . '/config/slides.json')
    ? file_get_contents(__DIR__ . '/config/slides.json')
    : '[]';
$db_slides = json_decode((string)$slides_raw, true) ?? [];

// Index DB slides by basename for fast lookup
$db_by_file = [];
foreach ($db_slides as $row) {
    $db_by_file[basename((string)$row['image_path'])] = $row;
}

// Build merged slide list
$slides = [];

// 1. Folder images (DB caption if available, else derive from filename)
foreach ($folder_slides as $path) {
    $rel  = 'assets/img/carousel/' . basename($path);
    $base = basename($path);
    if (isset($db_by_file[$base])) {
        $slides[] = ['src' => $rel, 'caption' => $db_by_file[$base]['caption']];
    } else {
        $name = pathinfo($base, PATHINFO_FILENAME);
        $slides[] = ['src' => $rel, 'caption' => ucwords(str_replace('_', ' ', $name))];
    }
}

// 2. DB-only slides (paths not found in folder)
$folder_basenames = array_map('basename', $folder_slides);
foreach ($db_slides as $row) {
    if (!in_array(basename((string)$row['image_path']), $folder_basenames, true)) {
        $slides[] = ['src' => $row['image_path'], 'caption' => $row['caption']];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero container">

  <!-- ===== Carousel ===== -->
  <div>
    <div class="carousel">
      <div class="carousel-stage">
        <?php if ($slides): $first = $slides[0]; ?>
          <img id="heroImg" src="<?= h($first['src']) ?>" alt="<?= h($first['caption']) ?>" />
          <div class="carousel-caption" id="heroCap"><?= h($first['caption']) ?></div>
        <?php else: ?>
          <div style="color:#aaa;padding:40px;text-align:center;">No slides configured</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="carousel-arrows">
      <button class="arrow-btn" id="prevBtn" aria-label="Previous">‹</button>
      <div class="thumbs" id="thumbs"></div>
      <button class="arrow-btn" id="nextBtn" aria-label="Next">›</button>
    </div>
  </div>

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

<script>
  const slides = <?= json_encode(
      array_map(fn($s) => ['src' => $s['src'], 'caption' => $s['caption']], $slides),
      JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
  ) ?>;
  const heroImg = document.getElementById('heroImg');
  const heroCap = document.getElementById('heroCap');
  const thumbsEl = document.getElementById('thumbs');
  let idx = 0;
  function render() {
    if (!slides.length) return;
    heroImg.src = slides[idx].src;
    heroCap.textContent = slides[idx].caption;
    [...thumbsEl.children].forEach((t, i) => t.classList.toggle('active', i === idx));
  }
  slides.forEach((s, i) => {
    const d = document.createElement('div');
    d.className = 'thumb' + (i === 0 ? ' active' : '');
    d.innerHTML = '<img src="' + s.src + '" alt="" />';
    d.addEventListener('click', () => { idx = i; render(); });
    thumbsEl.appendChild(d);
  });
  document.getElementById('prevBtn').addEventListener('click', () => { idx = (idx - 1 + slides.length) % slides.length; render(); });
  document.getElementById('nextBtn').addEventListener('click', () => { idx = (idx + 1) % slides.length; render(); });
  if (slides.length > 1) setInterval(() => { idx = (idx + 1) % slides.length; render(); }, 6000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
