<?php
declare(strict_types=1);

$_carousel_dir  = __DIR__ . '/../assets/img/carousel/';
$_carousel_json = __DIR__ . '/../config/slides.json';

$_images = glob($_carousel_dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
if (empty($_images)) {
    return;
}
natsort($_images);
$_images = array_values($_images);

$_raw      = is_readable($_carousel_json) ? (string) file_get_contents($_carousel_json) : '{}';
$_captions = json_decode($_raw, true);
if (!is_array($_captions)) {
    $_captions = [];
}

?>
<div class="col-12">
  <div id="irmCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <?php foreach ($_images as $_i => $_img):
        $_base    = basename($_img);
        $_caption = (string) ($_captions[$_base] ?? '');
        $_src     = 'assets/img/carousel/' . rawurlencode($_base);
      ?>
      <div class="carousel-item <?= $_i === 0 ? 'active' : '' ?>">
        <img src="<?= h($_src) ?>"
             class="d-block w-100"
             alt="<?= h($_caption ?: $_base) ?>"
             style="max-height:480px;object-fit:cover;">
        <?php if ($_caption !== ''): ?>
        <div class="carousel-caption d-none d-md-block">
          <p class="mb-0"><?= h($_caption) ?></p>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($_images) > 1): ?>
    <button class="carousel-control-prev" type="button"
            data-bs-target="#irmCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button"
            data-bs-target="#irmCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
  </div>
</div>
