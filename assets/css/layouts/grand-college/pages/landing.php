<?php
declare(strict_types=1);
// In scope: $render_slot (callable — fn(string $slot_id): void)
?>
<section class="layout-hero">
  <div class="container">
    <?php ($render_slot)('hero'); ?>
  </div>
</section>

<section class="layout-feature">
  <div class="container">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-0">
      <div class="col"><?php ($render_slot)('feature-1'); ?></div>
      <div class="col"><?php ($render_slot)('feature-2'); ?></div>
      <div class="col"><?php ($render_slot)('feature-3'); ?></div>
      <div class="col"><?php ($render_slot)('feature-4'); ?></div>
    </div>
  </div>
</section>

<section class="layout-highlight">
  <div class="container">
    <?php ($render_slot)('highlight'); ?>
  </div>
</section>

<section class="layout-stats">
  <div class="container">
    <div class="row row-cols-2 row-cols-md-4 g-0">
      <div class="col"><?php ($render_slot)('stat-1'); ?></div>
      <div class="col"><?php ($render_slot)('stat-2'); ?></div>
      <div class="col"><?php ($render_slot)('stat-3'); ?></div>
      <div class="col"><?php ($render_slot)('stat-4'); ?></div>
    </div>
  </div>
</section>

<section class="layout-cta">
  <div class="container">
    <?php ($render_slot)('cta'); ?>
  </div>
</section>
